<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Validator;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\ValidationRule;
use Override;

/**
 * Rejects `@narrow` usages the generator cannot turn into a narrowed class:
 * the inline fragment must sit directly in the selection set of a field that
 * returns an interface or union, name an object type, and be the only inline
 * fragment of that selection set.
 */
final class NarrowValidator extends ValidationRule
{
    #[Override]
    public function getVisitor(QueryValidationContext $context): array
    {
        /** @var array<int, true> $checked Inline fragments that sit directly inside a field */
        $checked = [];

        return [
            NodeKind::FIELD => static function (Node $node) use ($context, &$checked): void {
                if (!$node instanceof FieldNode || $node->selectionSet === null) {
                    return;
                }

                $narrowed = null;
                $inlineFragments = 0;

                foreach ($node->selectionSet->selections as $selection) {
                    if (!$selection instanceof InlineFragmentNode) {
                        continue;
                    }

                    ++$inlineFragments;

                    if (self::hasDirective($selection->directives, 'narrow')) {
                        $checked[spl_object_id($selection)] = true;
                        $narrowed ??= $selection;
                    }
                }

                if ($narrowed === null) {
                    return;
                }

                self::validateNarrowedField($node, $narrowed, $inlineFragments, $context);
            },
            NodeKind::INLINE_FRAGMENT => static function (Node $node) use ($context, &$checked): void {
                if (
                    !$node instanceof InlineFragmentNode
                    || !self::hasDirective($node->directives, 'narrow')
                    || isset($checked[spl_object_id($node)])
                ) {
                    return;
                }

                $context->reportError(
                    new Error(
                        sprintf(
                            '@narrow on "%s" can only be used on an inline fragment directly inside a field.',
                            self::describe($node),
                        ),
                        [$node],
                    ),
                );
            },
        ];
    }

    private static function validateNarrowedField(
        FieldNode $field,
        InlineFragmentNode $fragment,
        int $inlineFragments,
        QueryValidationContext $context,
    ): void {
        $name = $field->alias->value ?? $field->name->value;
        $fragmentName = self::describe($fragment);

        if ($inlineFragments > 1) {
            $context->reportError(
                new Error(
                    sprintf(
                        '@narrow on "%s" cannot be combined with other inline fragments on "%s".',
                        $fragmentName,
                        $name,
                    ),
                    [$fragment],
                ),
            );

            return;
        }

        $fieldType = $context->getType();

        if ($fieldType === null) {
            return;
        }

        $namedType = Type::getNamedType($fieldType);

        if (!$namedType instanceof InterfaceType && !$namedType instanceof UnionType) {
            $context->reportError(
                new Error(
                    sprintf(
                        '@narrow on "%s" requires "%s" to return an interface or union, got %s.',
                        $fragmentName,
                        $name,
                        $fieldType->toString(),
                    ),
                    [$fragment],
                ),
            );

            return;
        }

        $typeName = $fragment->typeCondition?->name->value;
        $narrowedType = $typeName !== null ? $context->getSchema()->getType($typeName) : null;

        if (!$narrowedType instanceof ObjectType) {
            $context->reportError(
                new Error(sprintf('@narrow on "%s" requires an object type.', $fragmentName), [$fragment]),
            );

            return;
        }

        if (self::hasDirective($fragment->directives, 'include') || self::hasDirective($fragment->directives, 'skip')) {
            $context->reportError(
                new Error(
                    sprintf('@narrow on "%s" cannot be combined with @include or @skip.', $fragmentName),
                    [$fragment],
                ),
            );
        }

        // The other selections end up on the narrowed class, so they must apply to the narrowed type.
        foreach ($field->selectionSet->selections ?? [] as $selection) {
            if (!$selection instanceof FragmentSpreadNode) {
                continue;
            }

            $spreadType = $context->getFragment($selection->name->value)?->typeCondition->name->value;

            if ($spreadType === null) {
                continue;
            }

            $type = $context->getSchema()->getType($spreadType);

            if (!$type instanceof ObjectType || $type->name() === $narrowedType->name()) {
                continue;
            }

            $context->reportError(
                new Error(
                    sprintf(
                        '@narrow on "%s" cannot be combined with fragment "%s" on %s.',
                        $fragmentName,
                        $selection->name->value,
                        $type->name(),
                    ),
                    [$selection],
                ),
            );
        }
    }

    private static function describe(InlineFragmentNode $fragment): string
    {
        return $fragment->typeCondition !== null ? '... on ' . $fragment->typeCondition->name->value : '...';
    }

    /**
     * @param NodeList<DirectiveNode> $directives
     */
    private static function hasDirective(NodeList $directives, string $name): bool
    {
        foreach ($directives as $directive) {
            if ($directive->name->value === $name) {
                return true;
            }
        }

        return false;
    }
}
