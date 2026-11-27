<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Validator;

use GraphQL\Error\Error;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\AbstractType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\ValidationRule;
use Override;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Throwable;

/**
 * Validates directives that turn a list into an array keyed by a field of its items, like `@indexBy`.
 */
final class ListKeyValidator extends ValidationRule
{
    /**
     * @param array<string, SymfonyType|array{SymfonyType, SymfonyType}> $scalars
     * @param array<string, bool> $directives The directive names, mapped to whether they accept several comma-separated fields
     */
    public function __construct(
        private array $scalars,
        private array $directives,
    ) {}

    #[Override]
    public function getVisitor(QueryValidationContext $context): array
    {
        return [
            NodeKind::FIELD => function (Node $node) use ($context): void {
                if (!$node instanceof FieldNode) {
                    return;
                }

                if ($node->directives->count() === 0) {
                    return;
                }

                $directiveName = null;
                $fields = null;
                foreach ($node->directives as $directive) {
                    if (!array_key_exists($directive->name->value, $this->directives)) {
                        continue;
                    }

                    if ($directive->arguments->count() === 0) {
                        continue;
                    }

                    if ($directive->arguments[0]->name->value !== 'field') {
                        continue;
                    }

                    if (!$directive->arguments[0]->value instanceof StringValueNode) {
                        continue;
                    }

                    $directiveName = $directive->name->value;
                    $fields = $directive->arguments[0]->value->value;

                    break;
                }

                if ($directiveName === null || $fields === null) {
                    return;
                }

                $type = $context->getType();

                if ($type instanceof NonNull) {
                    $type = $type->getWrappedType();
                }

                if (!$type instanceof ListOfType) {
                    $context->reportError(new Error(sprintf('@%s can only be used on lists', $directiveName), [$node]));

                    return;
                }

                $namedType = Type::getNamedType($type);
                $listOfType = $context->getSchema()->getType($namedType->name());

                if ($listOfType === null) {
                    return;
                }

                $fieldPaths = explode(',', $fields);

                if (!$this->directives[$directiveName] && count($fieldPaths) > 1) {
                    $context->reportError(
                        new Error(sprintf('@%s only accepts a single field', $directiveName), [$node]),
                    );

                    return;
                }

                foreach ($fieldPaths as $fieldPath) {
                    $fieldPath = trim($fieldPath);

                    if ($fieldPath === '') {
                        $context->reportError(
                            new Error(sprintf('Empty field in @%s directive', $directiveName), [$node]),
                        );

                        continue;
                    }

                    $fieldParts = explode('.', $fieldPath);

                    try {
                        $currentType = $listOfType;
                        foreach ($fieldParts as $index => $fieldName) {
                            $parentType = Type::getNamedType($currentType);

                            if ($parentType instanceof ObjectType || $parentType instanceof InterfaceType) {
                                if (!$parentType->hasField($fieldName)) {
                                    $context->reportError(
                                        new Error(
                                            sprintf(
                                                'Field "%s" is not defined for type "%s"',
                                                $fieldName,
                                                $parentType->name(),
                                            ),
                                            [$node],
                                        ),
                                    );

                                    continue 2; // Skip to next fieldPath
                                }

                                $field = $parentType->getField($fieldName);
                                $currentType = $field->getType();
                            } else {
                                $context->reportError(
                                    new Error(
                                        sprintf(
                                            'Cannot traverse field "%s" in path "%s" - parent is not an object or interface type',
                                            $fieldName,
                                            $fieldPath,
                                        ),
                                        [$node],
                                    ),
                                );

                                continue 2; // Skip to next fieldPath
                            }

                            if (!$currentType instanceof NonNull) {
                                $pathSoFar = implode('.', array_slice($fieldParts, 0, $index + 1));
                                $context->reportError(
                                    new Error(
                                        sprintf(
                                            'Field "%s" is nullable and cannot be used for @%s. All fields in the path must be non-null.',
                                            $pathSoFar,
                                            $directiveName,
                                        ),
                                        [$node],
                                    ),
                                );

                                continue 2; // Skip to next fieldPath
                            }

                            // Unwrap NonNull for next iteration
                            $currentType = $currentType->getWrappedType();
                        }

                        $namedType = Type::getNamedType($currentType);

                        $possibleArrayKeyTypes = [];
                        foreach ($this->scalars as $name => $scalarType) {
                            if (is_array($scalarType)) {
                                [$scalarType] = $scalarType;
                            }

                            if (!$scalarType instanceof SymfonyType\BuiltinType) {
                                continue;
                            }

                            if (!in_array(
                                $scalarType->getTypeIdentifier(),
                                [TypeIdentifier::STRING, TypeIdentifier::INT],
                                true,
                            )) {
                                continue;
                            }

                            $possibleArrayKeyTypes[] = $name;
                        }

                        if (
                            !$namedType instanceof EnumType
                            && !in_array($namedType->name(), $possibleArrayKeyTypes, true)
                        ) {
                            $context->reportError(
                                new Error(
                                    sprintf(
                                        '@%s(field: "%s") cannot be used because the field is not a valid array key type. Only String, Int, ID, and Enum types are allowed.',
                                        $directiveName,
                                        $fieldPath,
                                    ),
                                    [$node],
                                ),
                            );

                            continue;
                        }

                        if (!$this->isSelected($context, $node->selectionSet, $listOfType, $fieldParts)) {
                            $context->reportError(
                                new Error(
                                    sprintf(
                                        'Field "%s" is not selected in the %s directive',
                                        $fieldPath,
                                        $directiveName,
                                    ),
                                    [$node],
                                ),
                            );
                        }
                    } catch (Throwable $error) {
                        $context->reportError(
                            new Error(
                                sprintf(
                                    'Error processing @%s(field: "%s"): %s',
                                    $directiveName,
                                    $fieldPath,
                                    $error->getMessage(),
                                ),
                                [$node],
                            ),
                        );
                    }
                }
            },
        ];
    }

    /**
     * Whether the field path is selected, either directly or through an inline fragment or
     * fragment spread that applies to every item. Aliased fields don't count, because the
     * key is read from the payload by the field name.
     *
     * @param non-empty-list<string> $path
     * @param list<string> $visitedFragments
     */
    private function isSelected(
        QueryValidationContext $context,
        ?SelectionSetNode $selectionSet,
        Type $parentType,
        array $path,
        array $visitedFragments = [],
    ): bool {
        $parentType = Type::getNamedType($parentType);

        if ($selectionSet === null || !$parentType instanceof ObjectType && !$parentType instanceof InterfaceType) {
            return false;
        }

        [$fieldName] = $path;
        $rest = array_slice($path, 1);

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                if ($selection->name->value !== $fieldName) {
                    continue;
                }

                if ($selection->alias !== null && $selection->alias->value !== $fieldName) {
                    continue;
                }

                if ($rest === []) {
                    return true;
                }

                if (!$parentType->hasField($fieldName)) {
                    continue;
                }

                if ($this->isSelected(
                    $context,
                    $selection->selectionSet,
                    $parentType->getField($fieldName)->getType(),
                    $rest,
                    $visitedFragments,
                )) {
                    return true;
                }

                continue;
            }

            if ($selection instanceof InlineFragmentNode) {
                if (!$this->appliesToEveryItem($context->getSchema(), $parentType, $selection->typeCondition)) {
                    continue;
                }

                if ($this->isSelected($context, $selection->selectionSet, $parentType, $path, $visitedFragments)) {
                    return true;
                }

                continue;
            }

            if (!$selection instanceof FragmentSpreadNode) {
                continue;
            }

            $fragmentName = $selection->name->value;

            if (in_array($fragmentName, $visitedFragments, true)) {
                continue;
            }

            $fragment = $context->getFragment($fragmentName);

            if ($fragment === null) {
                continue;
            }

            if (!$this->appliesToEveryItem($context->getSchema(), $parentType, $fragment->typeCondition)) {
                continue;
            }

            if ($this->isSelected(
                $context,
                $fragment->selectionSet,
                $parentType,
                $path,
                [
                    ...$visitedFragments,
                    $fragmentName,
                ],
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * A fragment only guarantees its fields when every possible type of the parent matches its type condition.
     */
    private function appliesToEveryItem(
        Schema $schema,
        ObjectType|InterfaceType $parentType,
        ?NamedTypeNode $typeCondition,
    ): bool {
        if ($typeCondition === null || $typeCondition->name->value === $parentType->name()) {
            return true;
        }

        $condition = $schema->getType($typeCondition->name->value);

        if (!$condition instanceof AbstractType) {
            return false;
        }

        $possibleTypes = $parentType instanceof InterfaceType ? $schema->getPossibleTypes($parentType) : [$parentType];

        foreach ($possibleTypes as $possibleType) {
            if (!$schema->isSubType($condition, $possibleType)) {
                return false;
            }
        }

        return true;
    }
}
