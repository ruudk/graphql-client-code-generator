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
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\ValidationRule;
use Override;

/**
 * Rejects `@flatten` usages the generator cannot turn into a lifted property:
 * anything but a single object, conditional selections inside it, and lifted
 * fields that clash with a field the parent already exposes.
 */
final class FlattenValidator extends ValidationRule
{
    /**
     * @param list<string> $objectTypes GraphQL types mapped to a custom PHP type via `withObjectType()`
     */
    public function __construct(
        private readonly array $objectTypes = [],
    ) {}

    #[Override]
    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::FIELD => function (Node $node) use ($context): void {
                if (!$node instanceof FieldNode || !self::hasDirective($node->directives, 'flatten')) {
                    return;
                }

                $this->validateFlattenedField($node, $context);
            },
            NodeKind::SELECTION_SET => static function (Node $node) use ($context): void {
                if (!$node instanceof SelectionSetNode) {
                    return;
                }

                self::validateNameCollisions($node, $context);
            },
        ];
    }

    private function validateFlattenedField(FieldNode $node, QueryValidationContext $context): void
    {
        $name = $node->name->value;
        $type = $context->getType();

        if ($type === null) {
            return;
        }

        $nullableType = $type instanceof NonNull ? $type->getWrappedType() : $type;

        if ($nullableType instanceof ListOfType) {
            $context->reportError(
                new Error(
                    sprintf('@flatten on "%s" cannot be used on a list. Only a single object can be flattened.', $name),
                    [$node],
                ),
            );

            return;
        }

        $namedType = Type::getNamedType($type);

        if (!$namedType instanceof ObjectType) {
            $context->reportError(
                new Error(
                    sprintf('@flatten on "%s" requires an object type, got %s.', $name, $type->toString()),
                    [$node],
                ),
            );

            return;
        }

        if (in_array($namedType->name(), $this->objectTypes, true)) {
            $context->reportError(
                new Error(
                    sprintf(
                        '@flatten on "%s" cannot be used because %s is mapped to a custom PHP type.',
                        $name,
                        $namedType->name(),
                    ),
                    [$node],
                ),
            );

            return;
        }

        if (self::hasDirective($node->directives, 'include') || self::hasDirective($node->directives, 'skip')) {
            $context->reportError(
                new Error(
                    sprintf('@flatten on "%s" cannot be combined with @include or @skip.', $name),
                    [
                        $node,
                    ],
                ),
            );
        }

        if ($node->selectionSet === null) {
            return;
        }

        foreach ($node->selectionSet->selections as $selection) {
            if ($selection instanceof InlineFragmentNode) {
                $context->reportError(
                    new Error(
                        sprintf(
                            '@flatten on "%s" cannot contain inline fragments. Use a fragment spread instead.',
                            $name,
                        ),
                        [$selection],
                    ),
                );

                continue;
            }

            if (!$selection instanceof FieldNode && !$selection instanceof FragmentSpreadNode) {
                continue;
            }

            if (
                self::hasDirective($selection->directives, 'include')
                || self::hasDirective($selection->directives, 'skip')
            ) {
                $context->reportError(
                    new Error(
                        sprintf('@flatten on "%s" cannot contain selections with @include or @skip.', $name),
                        [$selection],
                    ),
                );
            }

            if ($selection instanceof FieldNode && self::hasDirective($selection->directives, 'hook')) {
                $context->reportError(
                    new Error(
                        sprintf('@flatten on "%s" cannot contain @hook fields.', $name),
                        [
                            $selection,
                        ],
                    ),
                );
            }
        }
    }

    /**
     * Every property a class exposes must have a unique name. Without `@flatten`
     * a duplicate response key is a field selected twice, which GraphQL merges;
     * with `@flatten` two different fields could end up as the same property.
     */
    private static function validateNameCollisions(
        SelectionSetNode $selectionSet,
        QueryValidationContext $context,
    ): void {
        /** @var array<string, list<FieldNode>> $fieldsByKey */
        $fieldsByKey = [];

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fieldsByKey[$selection->alias->value ?? $selection->name->value][] = $selection;
            }
        }

        $hasFlatten = false;

        foreach ($fieldsByKey as $key => $fields) {
            $flattened = array_filter($fields, static fn(FieldNode $field) => self::hasDirective(
                $field->directives,
                'flatten',
            ));

            if ($flattened === []) {
                continue;
            }

            $hasFlatten = true;

            if (count($flattened) !== count($fields)) {
                $context->reportError(
                    new Error(
                        sprintf('Field "%s" is selected more than once, but not every selection uses @flatten.', $key),
                        $fields,
                    ),
                );
            }
        }

        if (!$hasFlatten) {
            return;
        }

        /** @var array<string, string> $origins Property name => where it comes from */
        $origins = [];

        foreach (self::exposedNames($selectionSet) as [$name, $origin]) {
            if (isset($origins[$name]) && $origins[$name] !== $origin) {
                $context->reportError(
                    new Error(
                        sprintf(
                            '@flatten makes "%s" clash with another field of the same name (%s and %s). Use an alias to rename one of them.',
                            $name,
                            $origins[$name],
                            $origin,
                        ),
                        [$selectionSet],
                    ),
                );

                continue;
            }

            $origins[$name] = $origin;
        }
    }

    /**
     * The property names a selection set exposes on its class, each with a
     * description of where it comes from. Fields with the same origin are the
     * same field selected twice and are merged by the planner.
     *
     * @return iterable<array{string, string}>
     */
    private static function exposedNames(SelectionSetNode $selectionSet, string $prefix = ''): iterable
    {
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $key = $selection->alias->value ?? $selection->name->value;

                if ($selection->selectionSet !== null && self::hasDirective($selection->directives, 'flatten')) {
                    yield from self::exposedNames($selection->selectionSet, $prefix . $key . '.');

                    continue;
                }

                yield [$key, sprintf('"%s%s"', $prefix, $key)];

                continue;
            }

            if ($selection instanceof FragmentSpreadNode) {
                yield [lcfirst($selection->name->value), sprintf('"%s...%s"', $prefix, $selection->name->value)];

                continue;
            }

            if ($selection instanceof InlineFragmentNode && $selection->typeCondition !== null) {
                $typeName = $selection->typeCondition->name->value;

                yield ['as' . $typeName, sprintf('"%s... on %s"', $prefix, $typeName)];
            }
        }
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
