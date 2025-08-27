<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\SelectionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use LogicException;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\DirectiveProcessor;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\RecursiveTypeFinder;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
use Ruudk\GraphQLCodeGenerator\TypeMapper;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * Refactored planner with cleaner architecture
 */
final class SelectionSetPlanner
{
    public private(set) PlannerResult $result;

    /**
     * @var array<string, SymfonyType> Fragment name to payload shape mapping
     */
    public private(set) array $fragmentPayloadShapes = [];

    /**
     * @var array<string, Type&NamedType> Fragment name to type mapping
     */
    public private(set) array $fragmentTypes = [];

    /**
     * @var array<string, list<string>> Inline fragment required fields
     */
    public private(set) array $inlineFragmentRequiredFields = [];

    /**
     * @var array<string, SelectionSetResult> Fragment name to selection set result mapping
     */
    public private(set) array $fragmentSelectionResults = [];

    /**
     * @var array<string, FragmentDefinitionNode> Fragment name to definition mapping
     */
    public private(set) array $fragmentDefinitions = [];

    public function __construct(
        public private(set) readonly Config $config,
        private readonly Schema $schema,
        private readonly TypeMapper $typeMapper,
        private readonly DirectiveProcessor $directiveProcessor,
        private readonly EnglishInflector $inflector,
    ) {
        $this->result = new PlannerResult();
    }

    /**
     * Plan the selection set and return the result
     * @param null|list<SymfonyType> $indexByType
     * @param list<list<string>> $indexBy
     * @throws InvariantViolation
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function plan(
        SelectionSetNode $selectionSet,
        Type $parent,
        string $outputDirectory,
        string $fqcn,
        string $path,
        ?bool $nullable = null,
        ?array $indexByType = null,
        array $indexBy = [],
        bool $isGeneratingTopLevelFragment = false,
    ) : SelectionSetPlanResult {
        $context = new PlanningContext(
            outputDirectory: $outputDirectory,
            fqcn: $fqcn,
            path: $path,
            indexByType: $indexByType,
            indexBy: $indexBy,
            isGeneratingTopLevelFragment: $isGeneratingTopLevelFragment,
            isInsideFragmentContext: $isGeneratingTopLevelFragment, // If we're generating a fragment, we're in fragment context
        );

        $result = $this->planSelectionSet($selectionSet, $parent, $context, $nullable);

        // Store fragment results for later use
        // Only cache results from top-level fragment generation to ensure clean, unmerged results
        if ($path === 'fragment' && $isGeneratingTopLevelFragment && strpos($fqcn, '\\Fragment\\') !== false) {
            // Extract fragment name from FQCN
            $parts = explode('\\', $fqcn);
            $fragmentName = end($parts);

            // Store the fragment result
            $this->fragmentSelectionResults[$fragmentName] = $result;
        }

        return new SelectionSetPlanResult(
            fields: $result->fields->toArrayShape(),
            fields2: $result->pathFields->all(),
            payloadShape: $result->payloadShape->toArrayShape(),
            type: $result->resultType,
            plannerResult: $this->result,
            fragmentPayloadShapes: $this->fragmentPayloadShapes,
            fragmentTypes: $this->fragmentTypes,
            inlineFragmentRequiredFields: $this->inlineFragmentRequiredFields,
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws LogicException
     */
    public function planSelectionSet(
        SelectionSetNode $selectionSet,
        Type $type,
        PlanningContext $context,
        ?bool $nullable = null,
    ) : SelectionSetResult {
        // Handle wrapper types
        if ($type instanceof ListOfType) {
            $innerContext = $context->withPath($context->path . '.*');
            $wrappedType = $type->getWrappedType();

            // Check if items in the list are nullable
            $itemsNullable = ! ($wrappedType instanceof NonNull);

            // Plan the inner type
            $innerResult = $this->planSelectionSet($selectionSet, $wrappedType, $innerContext, true);

            // If items are nullable, wrap the result type in nullable
            if ($itemsNullable) {
                $innerResult = new SelectionSetResult(
                    fields: $innerResult->fields,
                    pathFields: $innerResult->pathFields,
                    payloadShape: $innerResult->payloadShape,
                    resultType: SymfonyType::nullable($innerResult->resultType),
                    wrappedFields: SymfonyType::nullable($innerResult->getFieldsType()),
                    wrappedPayloadShape: $innerResult->getPayloadShapeType(),
                );
            }

            return $this->wrapInList($innerResult, $context);
        }

        if ($type instanceof NonNull) {
            $innerResult = $this->planSelectionSet($selectionSet, $type->getWrappedType(), $context, false);

            return $innerResult; // NonNull doesn't change the structure
        }

        if ($type instanceof NullableType && $nullable === null) {
            $innerResult = $this->planSelectionSet($selectionSet, $type, $context, true);

            return $this->wrapInNullable($innerResult);
        }

        Assert::isInstanceOf($type, NamedType::class, 'Parent type must be a named type');

        return $this->planNamedTypeSelectionSet($selectionSet, $type, $context);
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws LogicException
     */
    private function planNamedTypeSelectionSet(
        SelectionSetNode $selectionSet,
        NamedType & Type $type,
        PlanningContext $context,
    ) : SelectionSetResult {
        $fields = new FieldCollection();
        $pathFields = new PathFieldMap();

        // Use PayloadShapeBuilder to build the complete payload shape
        $payloadShapeBuilder = new PayloadShapeBuilder(
            $this->schema,
            $this->typeMapper,
            $this->fragmentDefinitions,
            $this->fragmentTypes,
        );
        $payloadShape = $payloadShapeBuilder->buildPayloadShape($selectionSet, $type);

        // TODO This should not be part of the GraphQL operation, done by a visitor/optimizer.
        // Check if we need to implicitly add __typename
        if ($this->needsImplicitTypename($selectionSet, $type)) {
            $fields->add('__typename', SymfonyType::string());
            $pathFields->add('__typename', SymfonyType::string());
            // PayloadShapeBuilder already handles __typename when needed
        }

        // IMPORTANT: Collect and merge field selections from both direct selections and fragments
        // This ensures that fields selected in both places get the complete payload shape
        $mergedFieldSelections = $this->collectMergedFieldSelections($selectionSet, $type);

        // Process the merged field selections
        foreach ($mergedFieldSelections as $fieldName => $mergedSelection) {
            $this->processFieldSelection(
                $mergedSelection,
                $type,
                $context,
                $fields,
                $pathFields,
                $payloadShape,
            );
        }

        // Store state before inline fragments for merging
        $fieldsBeforeInlineFragments = $fields->clone();
        $payloadShapeBeforeInlineFragments = $payloadShape->clone();

        // Process inline fragments
        foreach ($selectionSet->selections as $selection) {
            if ( ! $selection instanceof InlineFragmentNode) {
                continue;
            }

            $this->processInlineFragment(
                $selection,
                $type,
                $context,
                $fields,
                $pathFields,
                $payloadShape,
                $fieldsBeforeInlineFragments,
                $payloadShapeBeforeInlineFragments,
                $selectionSet,
            );
        }

        // Process fragment spreads
        foreach ($selectionSet->selections as $selection) {
            if ( ! $selection instanceof FragmentSpreadNode) {
                continue;
            }

            $this->processFragmentSpread(
                $selection,
                $type,
                $context,
                $fields,
                $pathFields,
                $payloadShape,
            );
        }

        return new SelectionSetResult(
            fields: $fields,
            pathFields: $pathFields,
            payloadShape: $payloadShape,
            resultType: SymfonyType::object($context->fqcn),
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws LogicException
     */
    private function processFieldSelection(
        FieldNode $selection,
        Type $parent,
        PlanningContext $context,
        FieldCollection $fields,
        PathFieldMap $pathFields,
        PayloadShape $payloadShape,
    ) : void {
        $fieldName = $selection->alias->value ?? $selection->name->value;

        // Handle __typename specially
        if ($fieldName === '__typename') {
            $fields->add($fieldName, SymfonyType::string());
            $pathFields->add($fieldName, SymfonyType::string());
            $payloadShape->addRequired($fieldName, SymfonyType::string());

            return;
        }

        Assert::isInstanceOf($parent, HasFieldsType::class, 'Parent type must have fields');

        $fieldType = $parent->getField($selection->name->value)->getType();
        $nakedFieldType = $fieldType instanceof WrappingType
            ? $fieldType->getInnermostType()
            : $fieldType;

        // Handle predefined object types
        if ($nakedFieldType instanceof ObjectType && isset($this->config->objectTypes[$nakedFieldType->name()])) {
            [$objectPayloadShape, $objectType] = $this->config->objectTypes[$nakedFieldType->name()];

            $finalType = $fieldType instanceof NullableType
                ? SymfonyType::nullable($objectType)
                : $objectType;

            $finalPayloadShape = $fieldType instanceof NullableType
                ? SymfonyType::nullable($objectPayloadShape)
                : $objectPayloadShape;

            $fields->add($fieldName, $finalType);
            $payloadShape->addRequired($fieldName, $finalPayloadShape);

            return;
        }

        // Handle nested selection sets
        if ($selection->selectionSet !== null) {
            $this->processNestedSelection(
                $selection,
                $fieldName,
                $fieldType,
                $nakedFieldType,
                $parent,
                $context,
                $fields,
                $pathFields,
                $payloadShape,
            );

            return;
        }

        // Handle scalar fields
        $mappedType = $this->typeMapper->mapGraphQLTypeToPHPType($fieldType);
        $mappedPayloadType = $this->typeMapper->mapGraphQLTypeToPHPType($fieldType, builtInOnly: true);

        $fields->add($fieldName, $mappedType);
        $pathFields->addWithPrefix($context->path, $fieldName, $mappedType);
        $payloadShape->addRequired($fieldName, $mappedPayloadType);
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws LogicException
     */
    private function processNestedSelection(
        FieldNode $selection,
        string $fieldName,
        Type $fieldType,
        Type $nakedFieldType,
        Type $parent,
        PlanningContext $context,
        FieldCollection $fields,
        PathFieldMap $pathFields,
        PayloadShape $payloadShape,
    ) : void {
        Assert::isInstanceOf($nakedFieldType, NamedType::class, 'Field type must be a named type');

        // Determine class name
        $className = $this->determineClassName($fieldName, $nakedFieldType, $parent, $fieldType);

        // Handle indexBy directive
        $indexByContext = $this->processIndexByDirective($selection, $nakedFieldType);

        // Create nested context
        $nestedContext = $context
            ->withSubDirectory($className)
            ->withPath($context->path . '.' . $fieldName);

        if ($indexByContext !== null) {
            $nestedContext = $nestedContext->withIndexBy($indexByContext['types'], $indexByContext['fields']);
        }

        // Recursively plan the nested selection
        Assert::notNull($selection->selectionSet, 'Selection set must not be null');
        $nestedResult = $this->planSelectionSet(
            $selection->selectionSet,
            $fieldType,
            $nestedContext,
        );

        // Handle special nodes for connections
        $nodesType = $this->extractNodesType($nakedFieldType, $nestedResult, $context->path . '.' . $fieldName);

        // Create the class plan
        $this->createDataClassPlan(
            $nakedFieldType,
            $nestedResult,
            $nestedContext,
            $selection,
            $nodesType,
            $fieldType,
        );

        // Handle include/skip directives
        $resultType = $nestedResult->resultType;

        // Fields with @include/@skip are optional, but not necessarily nullable
        // The generator will handle making the property nullable when needed

        $fields->add($fieldName, $resultType);
        $pathFields->addWithPrefix($context->path, $fieldName, $resultType);
        $pathFields->merge($nestedResult->pathFields);

        // PayloadShapeBuilder has already built the complete merged payload shape including this field
        // Fields with include/skip directives are already marked as optional by PayloadShapeBuilder
        // We don't need to make them nullable - they're optional but non-null when present (if schema field is non-null)
        // For all fields, PayloadShapeBuilder has already handled the complete merged payload shape
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws LogicException
     */
    private function processInlineFragment(
        InlineFragmentNode $selection,
        Type $parent,
        PlanningContext $context,
        FieldCollection $fields,
        PathFieldMap $pathFields,
        PayloadShape $payloadShape,
        FieldCollection $fieldsBeforeInlineFragments,
        PayloadShape $payloadShapeBeforeInlineFragments,
        SelectionSetNode $parentSelectionSet,
    ) : void {
        Assert::notNull($selection->typeCondition, 'Inline fragment must have a type condition');

        $fragmentType = Type::getNamedType($this->schema->getType($selection->typeCondition->name->value));
        Assert::isInstanceOf($fragmentType, NamedType::class, 'Type condition must be a named type');

        $className = sprintf('As%s', $fragmentType->name());
        $fieldName = sprintf('as%s', $fragmentType->name());

        // Plan the inline fragment
        $fragmentContext = $context
            ->withSubDirectory($className)
            ->withPath($context->path . '.' . $fieldName);

        $fragmentResult = $this->planSelectionSet(
            $selection->selectionSet,
            $fragmentType,
            $fragmentContext,
        );

        // Merge parent fields into fragment
        $mergedFields = $fieldsBeforeInlineFragments->clone();
        $mergedFields->merge($fragmentResult->fields);

        $mergedPayloadShape = $payloadShapeBeforeInlineFragments->clone();
        $mergedPayloadShape->merge($fragmentResult->payloadShape);

        // For the inline fragment class itself, build a payload shape where fields are required
        // We need to include both parent fields and inline fragment fields
        $fragmentSpecificBuilder = new PayloadShapeBuilder(
            $this->schema,
            $this->typeMapper,
            $this->fragmentDefinitions,
            $this->fragmentTypes,
        );

        // First, get the parent fields that apply to this type
        // We need to filter out inline fragments and fragment spreads that are for other types
        $parentSelectionsForThisType = [];
        foreach ($parentSelectionSet->selections as $parentSelection) {
            if ($parentSelection instanceof FieldNode) {
                // Direct field selections always apply
                $parentSelectionsForThisType[] = $parentSelection;
            } elseif ($parentSelection instanceof InlineFragmentNode && $parentSelection === $selection) {
                // This is the current inline fragment we're processing - skip it to avoid duplication
                continue;
            } elseif ($parentSelection instanceof FragmentSpreadNode) {
                // Fragment spreads should NOT be included in inline fragment classes
                // They are isolated and accessed through their own accessor
                continue;
            }
            // Skip other inline fragments - they're for different types
        }

        // Create a combined selection set with parent fields and this inline fragment's fields
        $combinedSelectionSet = new SelectionSetNode([
            'selections' => new NodeList([
                ...$parentSelectionsForThisType,
                ...$selection->selectionSet->selections,
            ]),
        ]);

        // Build the payload shape with the combined selections
        // Using fragmentType as parent ensures fields won't be marked optional
        $inlineFragmentPayloadShape = $fragmentSpecificBuilder->buildPayloadShape($combinedSelectionSet, $fragmentType);

        // Add __typename as it's always present for inline fragments
        if ( ! $inlineFragmentPayloadShape->has('__typename')) {
            $inlineFragmentPayloadShape->addRequired('__typename', new StringLiteralType($fragmentType->name()));
        }

        // Store required fields for this inline fragment
        $this->storeInlineFragmentRequiredFields($selection, $context->fqcn . '\\' . $className);

        // Create the inline fragment class plan with the correct payload shape
        $this->createInlineFragmentClassPlan(
            $fragmentType,
            $mergedFields,
            $inlineFragmentPayloadShape,
            $fragmentContext,
            $selection,
        );

        // Add fragment accessor to parent
        $fragmentObjectType = new FragmentObjectType(
            $this->fullyQualified($context->fqcn, $className),
            $fragmentType->name(),
            $fragmentType,
        );

        $accessorType = $parent instanceof ObjectType
            ? $fragmentObjectType
            : SymfonyType::nullable($fragmentObjectType);

        $fields->add($fieldName, $accessorType);
        $pathFields->addWithPrefix($context->path, $fieldName, $accessorType);
        $pathFields->merge($fragmentResult->pathFields);

        // Merge inline fragment fields into parent payload as optional
        $this->mergeInlineFragmentPayload($fragmentResult->payloadShape, $payloadShape);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function processFragmentSpread(
        FragmentSpreadNode $selection,
        Type $parent,
        PlanningContext $context,
        FieldCollection $fields,
        PathFieldMap $pathFields,
        PayloadShape $payloadShape,
    ) : void {
        $fragmentType = $this->fragmentTypes[$selection->name->value];
        $fieldName = lcfirst($selection->name->value);

        // Always add the fragment wrapper for backward compatibility
        $fragmentObjectType = new FragmentObjectType(
            $this->fullyQualified('Fragment', $selection->name->value),
            $selection->name->value,
            $fragmentType,
        );

        $accessorType = ($parent instanceof InterfaceType || $parent instanceof UnionType)
            ? SymfonyType::nullable($fragmentObjectType)
            : $fragmentObjectType;

        $fields->add($fieldName, $accessorType);
        $pathFields->addWithPrefix($context->path, $fieldName, $accessorType);

        // Store required fields for conditional fragments (fragments on different type than parent)
        // This is needed for PHPStan type safety when generating getters
        // For interfaces/unions, always store required fields since they need type checking
        $needsFieldChecking = false;

        if ($parent instanceof InterfaceType || $parent instanceof UnionType) {
            // Interface/union parent always needs field checking for concrete type fragments
            $needsFieldChecking = true;
        } elseif ($parent instanceof NamedType && $fragmentType->name() !== $parent->name()) {
            // Different concrete types also need field checking
            $needsFieldChecking = true;
        }

        if ($needsFieldChecking) {
            // Fragment is on a different type than parent, so we need to check fields exist
            // We need to get ALL fields from the fragment, not just the direct ones
            $requiredFields = [];

            // Get the fragment definition to extract all its fields
            if (isset($this->fragmentDefinitions[$selection->name->value])) {
                $fragmentDef = $this->fragmentDefinitions[$selection->name->value];
                // Collect all fields from the fragment's selection set
                $this->collectRequiredFieldsFromSelectionSet($fragmentDef->selectionSet, $requiredFields);
            }

            // Store with the fragment's class name as key
            if ($requiredFields !== []) {
                $this->inlineFragmentRequiredFields[$fragmentObjectType->getClassName()] = $requiredFields;
            }
        }

        // Only merge fragment fields when:
        // 1. We're NOT generating a top-level fragment itself (to avoid fragments affecting each other)
        // 2. The fragment is on the SAME type as the parent (not a conditional fragment)
        // 3. There are other direct fields selected
        // 4. We have the fragment result available
        // Check if fragment type matches parent type
        // This handles both object types and interfaces
        // $fragmentType is already typed as Type&NamedType
        $isFragmentOnSameType = false;

        if ($parent instanceof NamedType) {
            $isFragmentOnSameType = $parent->name() === $fragmentType->name();
        }

        $hasDirectFields = false;
        $currentFields = $fields->getFields();
        foreach ($currentFields as $name => $type) {
            if ( ! str_starts_with($name, 'as') && $name !== '__typename') {
                $hasDirectFields = true;

                break;
            }
        }

        // Fragment field merging is context-dependent:
        // - In fragment context: strict isolation (no merging)
        // - In query context with same type AND no conflicting direct selections: merge for convenience
        // - When there are ANY direct field selections, maintain isolation per SPEC.md principle #3
        $hasAnyDirectFields = false;
        foreach ($fields->getFields() as $fName => $fType) {
            // Skip special fields and fragment accessors
            // TODO This is fragile, breaks easily
            if ($fName === '__typename' || str_starts_with($fName, 'as') || str_starts_with($fName, 'is')) {
                continue;
            }

            $nakedType = $this->typeMapper->getNakedType($fType);

            if ( ! $nakedType instanceof FragmentObjectType) {
                $hasAnyDirectFields = true;

                break;
            }
        }

        if ($isFragmentOnSameType && ! $context->isInsideFragmentContext && ! $hasAnyDirectFields && isset($this->fragmentSelectionResults[$selection->name->value])) {
            $fragmentResult = $this->fragmentSelectionResults[$selection->name->value];
            $fragmentFields = $fragmentResult->fields->getFields();

            // Merge fragment fields directly into the parent for direct access
            $currentFields = $fields->getFields();
            foreach ($fragmentFields as $fragmentFieldName => $fragmentFieldType) {
                // Skip the fragment wrapper field itself
                if ($fragmentFieldName === $fieldName) {
                    continue;
                }

                // Get the naked type to check what kind of field this is
                $nakedType = $this->typeMapper->getNakedType($fragmentFieldType);

                // Skip fragment accessors (both inline and named)
                if ($nakedType instanceof FragmentObjectType) {
                    continue;
                }

                // Skip boolean helper properties for fragments
                // Check if the type is boolean using TypeIdentifier
                // TODO This is fragile, breaks easily
                if (str_starts_with($fragmentFieldName, 'is') && $fragmentFieldType->isIdentifiedBy(\Symfony\Component\TypeInfo\TypeIdentifier::BOOL)) {
                    continue;
                }

                // Skip if field already exists (direct selection takes precedence)
                if (isset($currentFields[$fragmentFieldName])) {
                    continue;
                }

                // Add the field for direct access
                $fields->add($fragmentFieldName, $fragmentFieldType);
            }

            // Merge path fields from fragment
            if (isset($fragmentResult->pathFields)) {
                $pathFields->merge($fragmentResult->pathFields);
            }
        }

        // Note: PayloadShapeBuilder already handles merging fields from fragment spreads
        // The payload shape should already be correct at this point
    }

    /**
     * Collect and merge field selections from direct selections only
     * Fragment spread fields must remain isolated and are NOT collected here
     * @return array<string, FieldNode>
     */
    private function collectMergedFieldSelections(SelectionSetNode $selectionSet, NamedType & Type $type) : array
    {
        $fieldsByName = [];

        // Collect direct field selections ONLY
        // Fragment spread fields should NOT be collected here - they must remain isolated
        // and only accessible through fragment accessors
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fieldName = $selection->alias->value ?? $selection->name->value;

                if ( ! isset($fieldsByName[$fieldName])) {
                    $fieldsByName[$fieldName] = [];
                }

                $fieldsByName[$fieldName][] = $selection;
            }
        }

        // Merge selections for fields that appear multiple times
        $mergedFields = [];
        foreach ($fieldsByName as $fieldName => $selections) {
            if (count($selections) === 1) {
                $mergedFields[$fieldName] = $selections[0];
            } else {
                // Merge the selection sets of fields that appear multiple times
                $mergedFields[$fieldName] = $this->mergeFieldNodes($selections);
            }
        }

        return $mergedFields;
    }

    /**
     * Merge multiple FieldNodes into one with combined selection set
     * @param list<FieldNode> $nodes
     */
    private function mergeFieldNodes(array $nodes) : FieldNode
    {
        $primary = $nodes[0];

        // If it's a scalar field, just return the first one
        if ($primary->selectionSet === null) {
            return $primary;
        }

        // Collect all sub-selections
        $subFieldsByName = [];
        $otherSelections = []; // For fragments and inline fragments

        foreach ($nodes as $node) {
            if ($node->selectionSet === null) {
                continue;
            }

            foreach ($node->selectionSet->selections as $selection) {
                if ($selection instanceof FieldNode) {
                    $subFieldName = $selection->alias->value ?? $selection->name->value;

                    if ( ! isset($subFieldsByName[$subFieldName])) {
                        $subFieldsByName[$subFieldName] = [];
                    }

                    $subFieldsByName[$subFieldName][] = $selection;
                } else {
                    // Collect fragment spreads and inline fragments
                    $key = $this->getSelectionKey($selection);
                    $otherSelections[$key] = $selection;
                }
            }
        }

        // Recursively merge sub-fields
        $mergedSelections = [];
        foreach ($subFieldsByName as $subFieldName => $subFields) {
            if (count($subFields) === 1) {
                $mergedSelections[] = $subFields[0];
            } else {
                $mergedSelections[] = $this->mergeFieldNodes($subFields);
            }
        }

        // Add other selections
        foreach ($otherSelections as $selection) {
            $mergedSelections[] = $selection;
        }

        // Create merged FieldNode
        return new FieldNode([
            'name' => $primary->name,
            'alias' => $primary->alias,
            'arguments' => $primary->arguments,
            'directives' => $primary->directives,
            'selectionSet' => new SelectionSetNode([
                'selections' => new NodeList($mergedSelections),
            ]),
        ]);
    }

    /**
     * Get a unique key for a selection
     */
    private function getSelectionKey(SelectionNode $selection) : string
    {
        if ($selection instanceof FieldNode) {
            return 'field:' . ($selection->alias->value ?? $selection->name->value);
        } elseif ($selection instanceof InlineFragmentNode) {
            $typeName = $selection->typeCondition !== null ? $selection->typeCondition->name->value : 'unknown';

            return 'inline:' . $typeName;
        } elseif ($selection instanceof FragmentSpreadNode) {
            return 'spread:' . $selection->name->value;
        }

        return 'unknown:' . spl_object_id($selection);
    }

    /**
     * @param list<SymfonyType> $indexByType
     * @param list<list<string>> $indexBy
     * @throws LogicException
     */
    private function createIndexByType(array $indexByType, SymfonyType $valueType, array $indexBy) : SymfonyType
    {
        $result = $valueType;

        foreach (array_reverse($indexByType, true) as $i => $keyType) {
            $fieldPath = $indexBy[$i];

            if ($fieldPath === []) {
                continue;
            }

            $result = new IndexByCollectionType($keyType, $result, $fieldPath);
        }

        return $result;
    }

    /**
     * @throws LogicException
     */
    private function wrapInList(SelectionSetResult $inner, PlanningContext $context) : SelectionSetResult
    {
        $listFields = SymfonyType::list($inner->getFieldsType());
        $listPayloadShape = SymfonyType::list($inner->getPayloadShapeType());

        $resultType = $context->indexByType !== null && $context->indexBy !== []
            ? $this->createIndexByType($context->indexByType, $inner->resultType, $context->indexBy)
            : SymfonyType::list($inner->resultType);

        // Return the inner fields and payload with wrapped types
        return new SelectionSetResult(
            fields: $inner->fields,
            pathFields: $inner->pathFields,
            payloadShape: $inner->payloadShape,
            resultType: $resultType,
            wrappedFields: $listFields,
            wrappedPayloadShape: $listPayloadShape,
        );
    }

    private function wrapInNullable(SelectionSetResult $inner) : SelectionSetResult
    {
        // Wrap the types in nullable
        return new SelectionSetResult(
            fields: $inner->fields,
            pathFields: $inner->pathFields,
            payloadShape: $inner->payloadShape,
            resultType: SymfonyType::nullable($inner->resultType),
            wrappedFields: SymfonyType::nullable($inner->getFieldsType()),
            wrappedPayloadShape: SymfonyType::nullable($inner->getPayloadShapeType()),
        );
    }

    private function needsImplicitTypename(SelectionSetNode $selectionSet, Type $parent) : bool
    {
        if ( ! ($parent instanceof InterfaceType || $parent instanceof UnionType)) {
            return false;
        }

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof InlineFragmentNode) {
                return true;
            }
        }

        return false;
    }

    private function determineClassName(
        string $fieldName,
        NamedType $fieldType,
        Type $parent,
        Type $wrappedFieldType,
    ) : string {
        $className = ucfirst($this->isList($wrappedFieldType) ? $this->singularize($fieldName) : $fieldName);

        if ($parent instanceof NamedType) {
            if ($this->config->useNodeNameForEdgeNodes && $fieldName === 'node' && str_ends_with($parent->name(), 'Edge')) {
                return ucfirst($fieldType->name());
            }

            if ($this->config->useConnectionNameForConnections && str_ends_with($fieldType->name(), 'Connection')) {
                return ucfirst($fieldType->name());
            }

            if ($this->config->useEdgeNameForEdges && str_ends_with($fieldType->name(), 'Edge')) {
                return ucfirst($fieldType->name());
            }
        }

        return $className;
    }

    /**
     * @throws InvariantViolation
     * @throws InvalidArgumentException
     * @return array{types: list<SymfonyType>, fields: list<list<string>>}
     */
    private function processIndexByDirective(FieldNode $selection, Type $nakedFieldType) : ?array
    {
        if ( ! $this->config->indexByDirective) {
            return null;
        }

        $indexByFields = $this->directiveProcessor->getIndexByDirective($selection->directives);

        if ($indexByFields === []) {
            return null;
        }

        $validFields = array_filter($indexByFields, fn($fieldPath) => $fieldPath !== []);

        if ($validFields === []) {
            return null;
        }

        $types = array_map(
            fn($fieldPath) => $this->typeMapper->mapGraphQLTypeToPHPType(RecursiveTypeFinder::find($nakedFieldType, $fieldPath)),
            $validFields,
        );

        return [
            'types' => array_values($types),
            'fields' => array_values($validFields),
        ];
    }

    private function extractNodesType(
        NamedType $fieldType,
        SelectionSetResult $nestedResult,
        string $path,
    ) : ?SymfonyType {
        if ( ! $this->config->addNodesOnConnections || ! str_ends_with($fieldType->name(), 'Connection')) {
            return null;
        }

        $edges = $nestedResult->pathFields->get($path . '.edges');

        if ($edges === null) {
            return null;
        }

        $nodeType = $nestedResult->pathFields->get($path . '.edges.*.node');

        if ($nodeType === null) {
            return null;
        }

        if ($edges instanceof IndexByCollectionType) {
            // For multi-field indexing, nodes is still a list since we flatten the nested structure
            if ($edges->value instanceof IndexByCollectionType) {
                return SymfonyType::list($nodeType);
            }

            // For single-field indexing, nodes is an array with the same key as edges
            return SymfonyType::array($nodeType, $edges->key);
        }

        return SymfonyType::list($nodeType);
    }

    /**
     * @throws InvariantViolation
     */
    private function createDataClassPlan(
        NamedType & Type $parentType,
        SelectionSetResult $result,
        PlanningContext $context,
        FieldNode $selection,
        ?SymfonyType $nodesType,
        Type $fieldType,
    ) : void {
        $fields = $result->getFieldsType();
        $payloadShape = $result->getPayloadShapeType();

        // Unwrap list types
        if ($fields instanceof SymfonyType\CollectionType && $fields->isList()) {
            $fields = $fields->getCollectionValueType();
        }

        if ($payloadShape instanceof SymfonyType\CollectionType && $payloadShape->isList()) {
            $payloadShape = $payloadShape->getCollectionValueType();
        }

        $relativePath = str_replace($this->config->outputDir . '/', '', $context->outputDirectory . '.php');

        $dataClass = new DataClassPlan(
            relativePath: $relativePath,
            fqcn: $context->fqcn,
            parentType: $parentType,
            fields: $fields,
            payloadShape: $payloadShape,
            possibleTypes: $this->getPossibleTypes($fieldType),
            definitionNode: new InlineFragmentNode([
                'typeCondition' => new NamedTypeNode([
                    'name' => new NameNode([
                        'value' => $parentType->name(),
                    ]),
                ]),
                'selectionSet' => $selection->selectionSet,
            ]),
            nodesType: $nodesType,
            inlineFragmentRequiredFields: $this->inlineFragmentRequiredFields,
            isData: false,
            isFragment: false,
        );

        $this->result->addClass($dataClass);
    }

    private function createInlineFragmentClassPlan(
        NamedType & Type $fragmentType,
        FieldCollection $fields,
        PayloadShape $payloadShape,
        PlanningContext $context,
        InlineFragmentNode $selection,
    ) : void {
        $relativePath = str_replace($this->config->outputDir . '/', '', $context->outputDirectory . '.php');

        $dataClass = new DataClassPlan(
            relativePath: $relativePath,
            fqcn: $context->fqcn,
            parentType: $fragmentType,
            fields: $fields->toArrayShape(),
            payloadShape: $payloadShape->toArrayShape(),
            possibleTypes: [$fragmentType->name()],
            definitionNode: $selection,
            nodesType: null,
            inlineFragmentRequiredFields: $this->inlineFragmentRequiredFields,
            isData: false,
            isFragment: true,
        );

        $this->result->addClass($dataClass);
    }

    private function storeInlineFragmentRequiredFields(
        InlineFragmentNode $selection,
        string $inlineFragmentKey,
    ) : void {
        $requiredFields = [];
        $this->collectRequiredFieldsFromSelectionSet($selection->selectionSet, $requiredFields);
        $this->inlineFragmentRequiredFields[$inlineFragmentKey] = $requiredFields;
    }

    /**
     * Recursively collect all field names from a selection set
     * @param list<string> $requiredFields
     */
    private function collectRequiredFieldsFromSelectionSet(
        SelectionSetNode $selectionSet,
        array &$requiredFields,
    ) : void {
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode && $selection->name->value !== '__typename') {
                $fieldName = $selection->alias->value ?? $selection->name->value;

                if ( ! in_array($fieldName, $requiredFields, true)) {
                    $requiredFields[] = $fieldName;
                }
            } elseif ($selection instanceof FragmentSpreadNode) {
                // Fragment spreads also contribute required fields
                $fragmentName = $selection->name->value;

                if (isset($this->fragmentDefinitions[$fragmentName])) {
                    $this->collectRequiredFieldsFromSelectionSet(
                        $this->fragmentDefinitions[$fragmentName]->selectionSet,
                        $requiredFields,
                    );
                }
            }
        }
    }

    private function mergeInlineFragmentPayload(
        PayloadShape $fragmentPayload,
        PayloadShape $parentPayload,
    ) : void {
        $parentPayload->merge($fragmentPayload, asOptional: true);
    }

    // Utility methods

    private function isList(Type $fieldType) : bool
    {
        if ($fieldType instanceof NonNull) {
            return $this->isList($fieldType->getWrappedType());
        }

        return $fieldType instanceof ListOfType;
    }

    private function singularize(string $fieldName) : string
    {
        $options = $this->inflector->singularize($fieldName);

        return $options[0] ?? $fieldName;
    }

    private function fullyQualified(string $part, string ...$moreParts) : string
    {
        if (str_starts_with($part, $this->config->namespace . '\\')) {
            $part = substr($part, strlen($this->config->namespace) + 1);
        }

        return implode('\\', array_filter([$this->config->namespace, $part, ...$moreParts], fn($part) => $part !== ''));
    }

    /**
     * @throws InvariantViolation
     * @return list<string>
     */
    private function getPossibleTypes(Type $type) : array
    {
        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }

        if ($type instanceof UnionType) {
            $possible = [];
            foreach ($type->getTypes() as $possibleType) {
                $possible[] = $possibleType->name;
            }

            return $possible;
        }

        if ($type instanceof InterfaceType) {
            $possible = [];
            foreach ($this->schema->getImplementations($type)->objects() as $possibleType) {
                $possible[] = $possibleType->name;
            }

            return $possible;
        }

        return [];
    }

    public function setFragmentPayloadShape(string $name, SymfonyType $shape) : void
    {
        $this->fragmentPayloadShapes[$name] = $shape;
    }

    public function setFragmentType(string $name, NamedType & Type $type) : void
    {
        $this->fragmentTypes[$name] = $type;
    }

    public function setFragmentDefinition(string $name, FragmentDefinitionNode $definition) : void
    {
        $this->fragmentDefinitions[$name] = $definition;
    }
}
