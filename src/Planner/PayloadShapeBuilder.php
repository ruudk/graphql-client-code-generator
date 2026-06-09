<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use Ruudk\GraphQLCodeGenerator\GraphQL\PossibleTypesFinder;
use Ruudk\GraphQLCodeGenerator\TypeMapper;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Webmozart\Assert\Assert;

/**
 * Builds payload shapes from GraphQL selection sets
 *
 * The payload shape represents the complete data structure that the GraphQL server
 * will return, including all fields from direct selections and fragments.
 */
final readonly class PayloadShapeBuilder
{
    private PossibleTypesFinder $possibleTypesFinder;

    /**
     * @param array<string, array{FragmentDefinitionNode, list<string>}> $fragmentDefinitions
     * @param array<string, Type&NamedType> $fragmentTypes
     */
    public function __construct(
        private Schema $schema,
        private TypeMapper $typeMapper,
        private array $fragmentDefinitions = [],
        private array $fragmentTypes = [],
    ) {
        $this->possibleTypesFinder = new PossibleTypesFinder($this->schema);
    }

    /**
     * Build a payload shape from a selection set
     * @throws \Webmozart\Assert\InvalidArgumentException
     * @throws \GraphQL\Error\InvariantViolation
     */
    public function buildPayloadShape(
        SelectionSetNode $selectionSet,
        Type $parentType,
    ) : PayloadShape {
        $shape = new PayloadShape();
        $nakedParentType = $this->unwrapType($parentType);
        Assert::isInstanceOf($nakedParentType, NamedType::class);

        $this->processSelections($selectionSet, $nakedParentType, $shape);

        return $shape;
    }

    /**
     * Process all selections in a unified way
     * @param array<string, true> $visitedFragments
     * @throws \Webmozart\Assert\InvalidArgumentException
     * @throws \GraphQL\Error\InvariantViolation
     */
    private function processSelections(
        SelectionSetNode $selectionSet,
        NamedType $parentType,
        PayloadShape $shape,
        array $visitedFragments = [],
    ) : void {
        // First, collect all field selections including from same-type fragments
        $fieldGroups = $this->collectAllFieldSelections($selectionSet, $parentType, $visitedFragments);

        // Process the collected field selections
        foreach ($fieldGroups as $fieldName => $selections) {
            $fieldType = $this->getFieldType($selections[0], $parentType);
            $fieldPayloadShape = $this->buildFieldPayloadShape($selections, $fieldType);

            // Fields with conditional directives should be optional
            $hasConditionalDirective = array_any($selections, $this->hasConditionalDirectives(...));

            if ($hasConditionalDirective) {
                $shape->addOptional($fieldName, $fieldPayloadShape);
            } else {
                $shape->addRequired($fieldName, $fieldPayloadShape);
            }
        }

        // Process inline fragments and type-specific/conditional fragment spreads
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof InlineFragmentNode) {
                $this->processInlineFragment($selection, $shape, $parentType, $visitedFragments);

                continue;
            }

            if ( ! $selection instanceof FragmentSpreadNode) {
                continue;
            }

            $fragmentName = $selection->name->value;

            if ( ! isset($this->fragmentDefinitions[$fragmentName])) {
                continue;
            }

            $fragmentType = $this->fragmentTypes[$fragmentName];

            // Skip if same-type object fragment without directives (already processed in field collection)
            $isSameType = $fragmentType->name() === $parentType->name();
            $hasDirective = $this->hasConditionalDirectives($selection);
            $isUnionOrInterface = $parentType instanceof UnionType || $parentType instanceof InterfaceType;

            // Process separately if: different type, has directives, or is union/interface
            if ( ! $isSameType || $hasDirective || $isUnionOrInterface) {
                $this->processFragmentSpread($selection, $shape, $parentType, $visitedFragments);
            }
        }

        // When the parent is polymorphic and at least one concrete-type variant
        // was registered, enumerate the schema's remaining possible types as
        // empty variant arms. This keeps `__typename === 'X'` from being
        // `always-true` when the client only wrote a fragment for one of
        // several possible types: PHPStan sees the other typenames as live
        // alternatives.
        if (
            $shape->hasVariants()
            && ($parentType instanceof UnionType || $parentType instanceof InterfaceType)
        ) {
            foreach ($this->possibleTypesFinder->find($parentType) as $possibleTypeName) {
                if ( ! $shape->hasVariant($possibleTypeName)) {
                    $shape->addVariant($possibleTypeName, new PayloadShape());
                }
            }
        }
    }

    /**
     * Collect all field selections including from same-type fragments
     * @param array<string, true> $visitedFragments
     * @throws \GraphQL\Error\InvariantViolation
     * @return array<string, list<FieldNode>>
     */
    private function collectAllFieldSelections(
        SelectionSetNode $selectionSet,
        NamedType $parentType,
        array $visitedFragments,
    ) : array {
        $fieldGroups = [];
        $fragmentSpreads = [];

        // First pass: collect all direct field selections
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                if ($this->hasHookDirective($selection)) {
                    continue;
                }

                $fieldName = $selection->alias->value ?? $selection->name->value;
                $fieldGroups[$fieldName] ??= [];
                $fieldGroups[$fieldName][] = $selection;

                continue;
            }

            if ($selection instanceof FragmentSpreadNode) {
                // Save fragment spreads for second pass
                $fragmentSpreads[] = $selection;
            }
        }

        // Second pass: process fragment spreads and merge their fields
        foreach ($fragmentSpreads as $selection) {
            $fragmentName = $selection->name->value;

            // Skip if already visited
            if (isset($visitedFragments[$fragmentName])) {
                continue;
            }

            // Skip if undefined
            if ( ! isset($this->fragmentDefinitions[$fragmentName])) {
                continue;
            }

            // Skip if has conditional directives
            if ($this->hasConditionalDirectives($selection)) {
                continue;
            }

            $fragmentType = $this->fragmentTypes[$fragmentName];

            // Skip if parent is union/interface (needs special processing)
            $isUnionOrInterface = $parentType instanceof UnionType || $parentType instanceof InterfaceType;

            if ($isUnionOrInterface) {
                continue;
            }

            $isSameType = $fragmentType->name() === $parentType->name();

            // Collect direct fields when the spread is either same-type, or
            // the concrete parent satisfies the fragment's abstract type
            // (i.e. it implements the interface or is a union member). For
            // implementor spreads the abstract fragment's fields are present
            // unconditionally on the parent at runtime, so its direct fields
            // belong in the same group.
            if (
                ! $isSameType
                && ! ($parentType instanceof ObjectType && $this->parentSatisfiesAbstract($parentType, $fragmentType))
            ) {
                continue;
            }

            // Collect fields from this fragment
            $fragmentDef = $this->fragmentDefinitions[$fragmentName][0];
            $newVisited = [
                ...$visitedFragments,
                $fragmentName => true,
            ];
            $fragmentFields = $this->collectAllFieldSelections($fragmentDef->selectionSet, $parentType, $newVisited);

            // Merge fragment fields into our groups
            foreach ($fragmentFields as $fieldName => $selections) {
                $fieldGroups[$fieldName] ??= [];
                $fieldGroups[$fieldName] = array_merge($fieldGroups[$fieldName], $selections);
            }
        }

        return $fieldGroups;
    }

    /**
     * Build the payload shape for a field (potentially merging multiple selections)
     * @param list<FieldNode> $selections
     * @throws \Webmozart\Assert\InvalidArgumentException
     * @throws \GraphQL\Error\InvariantViolation
     */
    private function buildFieldPayloadShape(
        array $selections,
        Type $fieldType,
    ) : SymfonyType {
        $nakedFieldType = $this->unwrapType($fieldType);

        // Handle scalar/enum types (including __typename which is String!)
        if ($nakedFieldType instanceof ScalarType || $nakedFieldType instanceof EnumType) {
            return $this->typeMapper->mapGraphQLTypeToPHPType($fieldType, null, true);
        }

        // Handle object types
        Assert::isInstanceOf($nakedFieldType, NamedType::class);

        // Check if this is an object type with a custom mapping
        if ($nakedFieldType instanceof ObjectType) {
            // Try to get the payload type from custom mappings
            // Don't pass null to avoid double-wrapping with nullable
            // We'll apply the wrapping later with applyFieldWrapping
            $mappedType = $this->typeMapper->mapGraphQLTypeToPHPType($nakedFieldType, true, true);

            // Check if we got a custom mapping (not mixed at the base)
            $unwrappedType = $mappedType;

            // Only unwrap actual list/array collections, not array shapes
            if ($unwrappedType instanceof SymfonyType\CollectionType && $unwrappedType->isList()) {
                $unwrappedType = $unwrappedType->getCollectionValueType();
            }

            // If we got an array shape, it means there's a custom mapping
            if ($unwrappedType instanceof SymfonyType\ArrayShapeType) {
                // Apply the field wrapping to the custom mapping
                return $this->applyFieldWrapping($fieldType, $mappedType);
            }
        }

        // Otherwise, build recursively
        $mergedSelectionSet = $this->mergeSelectionSets($selections);
        $nestedShape = $this->buildPayloadShape($mergedSelectionSet, $nakedFieldType);

        // Apply wrapping (list/nullable)
        return $this->applyFieldWrapping($fieldType, $nestedShape->toArrayShape());
    }

    /**
     * Merge selection sets from multiple field selections
     * @param list<FieldNode> $selections
     */
    private function mergeSelectionSets(array $selections) : SelectionSetNode
    {
        $mergedSelections = [];
        foreach ($selections as $selection) {
            if ($selection->selectionSet !== null) {
                foreach ($selection->selectionSet->selections as $subSelection) {
                    $mergedSelections[] = $subSelection;
                }
            }
        }

        return new SelectionSetNode([
            'selections' => new NodeList($mergedSelections),
        ]);
    }

    /**
     * Apply wrapping (list/nullable) to a type
     */
    private function applyFieldWrapping(Type $fieldType, SymfonyType $shape) : SymfonyType
    {
        // Special handling for ListOfType to check if items are nullable
        if ($fieldType instanceof ListOfType) {
            $innerType = $fieldType->getWrappedType();

            // If the inner type is not NonNull, items are nullable
            if ( ! ($innerType instanceof NonNull)) {
                $shape = SymfonyType::nullable($shape);
            }

            // Wrap in list and the list itself is nullable
            return SymfonyType::nullable(SymfonyType::list($shape));
        }

        // NonNull wrapper
        if ($fieldType instanceof NonNull) {
            $innerType = $fieldType->getWrappedType();

            if ($innerType instanceof ListOfType) {
                $listItemType = $innerType->getWrappedType();

                // If list items are not NonNull, they are nullable
                if ( ! ($listItemType instanceof NonNull)) {
                    $shape = SymfonyType::nullable($shape);
                }

                // Non-nullable list
                return SymfonyType::list($shape);
            }

            // Non-nullable scalar/object
            return $shape;
        }

        // Default case: nullable type
        return SymfonyType::nullable($shape);
    }

    /**
     * Process inline fragments
     * @param array<string, true> $visitedFragments
     * @throws \GraphQL\Error\InvariantViolation
     * @throws \Webmozart\Assert\InvalidArgumentException
     */
    private function processInlineFragment(
        InlineFragmentNode $fragment,
        PayloadShape $shape,
        NamedType $parentType,
        array $visitedFragments,
    ) : void {
        if ($fragment->typeCondition === null) {
            // Fragment without type condition applies to parent type
            $this->processSelections($fragment->selectionSet, $parentType, $shape, $visitedFragments);

            return;
        }

        $fragmentTypeName = $fragment->typeCondition->name->value;
        $fragmentType = $this->schema->getType($fragmentTypeName);
        Assert::isInstanceOf($fragmentType, NamedType::class);

        $isSameType = $fragmentType->name() === $parentType->name();
        $parentIsPolymorphic = $parentType instanceof UnionType || $parentType instanceof InterfaceType;

        // Same-type fragment on union/interface spreads into common fields.
        if ($isSameType && $parentIsPolymorphic) {
            $this->processSelections($fragment->selectionSet, $fragmentType, $shape, $visitedFragments);

            return;
        }

        // Polymorphic parent → variant arms keyed by concrete `__typename`.
        if ($parentIsPolymorphic) {
            $variantShape = new PayloadShape();
            $this->processSelections($fragment->selectionSet, $fragmentType, $variantShape, $visitedFragments);
            $this->distributeVariant($shape, $fragmentType, $variantShape);

            return;
        }

        // Concrete parent that satisfies the fragment's type condition: process
        // the inline fragment's selection set against the concrete parent so
        // nested abstract spreads resolve in concrete context.
        if (
            $parentType instanceof ObjectType
            && $this->parentSatisfiesAbstract($parentType, $fragmentType)
        ) {
            $this->processSelections($fragment->selectionSet, $parentType, $shape, $visitedFragments);

            return;
        }

        // Fallback (concrete parent, different fragment type): merge with optional.
        $fragmentShape = new PayloadShape();
        $this->processSelections($fragment->selectionSet, $fragmentType, $fragmentShape, $visitedFragments);
        $isOptional = $this->shouldFieldsBeOptional($parentType, $fragmentType, false);
        $shape->merge($fragmentShape, $isOptional);
    }

    /**
     * Add a fragment's fields to the appropriate variant arm(s). When the
     * fragment type is an interface or union, the fields apply to every
     * concrete implementor of that abstract type — we add the variant to each
     * one. `__typename` only ever holds a concrete object type's name at
     * runtime, so abstract type names never appear as their own arms.
     *
     * @throws \GraphQL\Error\InvariantViolation
     */
    private function distributeVariant(
        PayloadShape $shape,
        NamedType $fragmentType,
        PayloadShape $variantShape,
    ) : void {
        if ($fragmentType instanceof ObjectType) {
            $shape->addVariant($fragmentType->name(), $variantShape);

            return;
        }

        if ($fragmentType instanceof UnionType || $fragmentType instanceof InterfaceType) {
            foreach ($this->possibleTypesFinder->find($fragmentType) as $concreteTypeName) {
                $shape->addVariant($concreteTypeName, $variantShape);
            }
        }
    }

    /**
     * Process fragment spreads (for different-type or conditional fragments)
     * @param array<string, true> $visitedFragments
     * @throws \Webmozart\Assert\InvalidArgumentException
     * @throws \GraphQL\Error\InvariantViolation
     */
    private function processFragmentSpread(
        FragmentSpreadNode $spread,
        PayloadShape $shape,
        NamedType $parentType,
        array $visitedFragments,
    ) : void {
        $fragmentName = $spread->name->value;

        // Skip if already visited
        if (isset($visitedFragments[$fragmentName])) {
            return;
        }

        $fragmentType = $this->fragmentTypes[$fragmentName];
        $fragmentDef = $this->fragmentDefinitions[$fragmentName][0];
        $newVisited = [
            ...$visitedFragments,
            $fragmentName => true,
        ];

        $hasDirective = $this->hasConditionalDirectives($spread);
        $isSameType = $fragmentType->name() === $parentType->name();
        $parentIsPolymorphic = $parentType instanceof UnionType || $parentType instanceof InterfaceType;

        // For same-type interface/union fragments without directives,
        // merge directly to preserve field requiredness
        if ($isSameType && ! $hasDirective && $parentIsPolymorphic) {
            $this->processSelections($fragmentDef->selectionSet, $fragmentType, $shape, $newVisited);

            return;
        }

        // Polymorphic parent → variant arms keyed by concrete `__typename`.
        if ($parentIsPolymorphic && ! $hasDirective) {
            $variantShape = new PayloadShape();
            $this->processSelections($fragmentDef->selectionSet, $fragmentType, $variantShape, $newVisited);
            $this->distributeVariant($shape, $fragmentType, $variantShape);

            return;
        }

        // Concrete parent that satisfies the fragment's type condition (parent
        // implements an interface fragment, or is a member of a union
        // fragment). The fragment's fields apply unconditionally — process its
        // selection set with the concrete parent so that any deeper abstract
        // spreads also resolve against the same concrete type.
        if (
            ! $hasDirective
            && $parentType instanceof ObjectType
            && ! $isSameType
            && $this->parentSatisfiesAbstract($parentType, $fragmentType)
        ) {
            $this->processSelections($fragmentDef->selectionSet, $parentType, $shape, $newVisited);

            return;
        }

        // Create sub-shape and merge with optionality
        $fragmentShape = new PayloadShape();
        $this->processSelections($fragmentDef->selectionSet, $fragmentType, $fragmentShape, $newVisited);

        $isOptional = $this->shouldFieldsBeOptional($parentType, $fragmentType, $hasDirective);
        $shape->merge($fragmentShape, $isOptional);
    }

    /**
     * Is the concrete object type a runtime member of the fragment's abstract
     * type? True when `$parentType` implements an interface fragment, or is
     * listed in a union fragment.
     *
     * @throws \GraphQL\Error\InvariantViolation
     */
    private function parentSatisfiesAbstract(ObjectType $parentType, NamedType $fragmentType) : bool
    {
        if ($fragmentType instanceof InterfaceType) {
            return $parentType->implementsInterface($fragmentType);
        }

        if ($fragmentType instanceof UnionType) {
            foreach ($fragmentType->getTypes() as $member) {
                if ($member->name === $parentType->name()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function shouldFieldsBeOptional(
        NamedType $parentType,
        NamedType $fragmentType,
        bool $hasDirective,
    ) : bool {
        if ($hasDirective) {
            return true;
        }

        if ($parentType instanceof UnionType || $parentType instanceof InterfaceType) {
            return true;
        }

        if ($parentType instanceof ObjectType && $fragmentType->name() !== $parentType->name()) {
            return true;
        }

        return false;
    }

    /**
     * Get the type of a field from its parent type
     * @throws \Webmozart\Assert\InvalidArgumentException
     * @throws \GraphQL\Error\InvariantViolation
     */
    private function getFieldType(FieldNode $field, NamedType $parentType) : Type
    {
        if ($field->name->value === '__typename') {
            return Type::nonNull(Type::string());
        }

        Assert::isInstanceOf($parentType, HasFieldsType::class);

        return $parentType->getField($field->name->value)->getType();
    }

    /**
     * Unwrap a type to get the underlying named type
     */
    private function unwrapType(Type $type) : Type
    {
        if ($type instanceof WrappingType) {
            return $this->unwrapType($type->getInnermostType());
        }

        return $type;
    }

    /**
     * Check if a node has @include or @skip directive
     */
    private function hasConditionalDirectives(FieldNode | FragmentSpreadNode $node) : bool
    {
        foreach ($node->directives as $directive) {
            $name = $directive->name->value;

            if ($name === 'include' || $name === 'skip') {
                return true;
            }
        }

        return false;
    }

    private function hasHookDirective(FieldNode $node) : bool
    {
        foreach ($node->directives as $directive) {
            if ($directive->name->value === 'hook') {
                return true;
            }
        }

        return false;
    }
}
