<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NameNode;
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
use Ruudk\GraphQLCodeGenerator\TypeMapper;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
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
     * @param list<string> $indexBy
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
        ?SymfonyType $indexByType = null,
        array $indexBy = [],
    ) : SelectionSetPlanResult {
        $context = new PlanningContext(
            outputDirectory: $outputDirectory,
            fqcn: $fqcn,
            path: $path,
            indexByType: $indexByType,
            indexBy: $indexBy,
        );

        $result = $this->planSelectionSet($selectionSet, $parent, $context, $nullable);

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
            $innerResult = $this->planSelectionSet($selectionSet, $type->getWrappedType(), $innerContext, true);

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
        $payloadShape = new PayloadShape();

        // Check if we need to implicitly add __typename
        if ($this->needsImplicitTypename($selectionSet, $type)) {
            $fields->add('__typename', SymfonyType::string());
            $pathFields->add('__typename', SymfonyType::string());
            $payloadShape->addRequired('__typename', SymfonyType::string());
        }

        // NOTE: Processor-based approach is disabled until inline fragment processing is fixed
        // The processors are initialized but not used to maintain the refactored structure
        // while falling back to the original implementation

        // Fallback to original implementation
        // Process fields first
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $this->processFieldSelection(
                    $selection,
                    $type,
                    $context,
                    $fields,
                    $pathFields,
                    $payloadShape,
                );
            }
        }

        // Store state before inline fragments for merging
        $fieldsBeforeInlineFragments = $fields->clone();
        $payloadShapeBeforeInlineFragments = $payloadShape->clone();

        // Process inline fragments
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof InlineFragmentNode) {
                $this->processInlineFragment(
                    $selection,
                    $type,
                    $context,
                    $fields,
                    $pathFields,
                    $payloadShape,
                    $fieldsBeforeInlineFragments,
                    $payloadShapeBeforeInlineFragments,
                );
            }
        }

        // Process fragment spreads
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FragmentSpreadNode) {
                $this->processFragmentSpread(
                    $selection,
                    $type,
                    $context,
                    $fields,
                    $pathFields,
                    $payloadShape,
                );
            }
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
            $this->processNestedSelectionLegacy(
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
    private function processNestedSelectionLegacy(
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
            /** @var array{type: SymfonyType, fields: list<string>} $indexByContext */
            $nestedContext = $nestedContext->withIndexBy($indexByContext['type'], $indexByContext['fields']);
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
        $resultPayloadShape = $nestedResult->getPayloadShapeType();

        if ($this->directiveProcessor->hasIncludeOrSkipDirective($selection->directives)) {
            $resultType = SymfonyType::nullable($resultType);
            $resultPayloadShape = SymfonyType::nullable($resultPayloadShape);
        }

        $fields->add($fieldName, $resultType);
        $pathFields->addWithPrefix($context->path, $fieldName, $resultType);
        $pathFields->merge($nestedResult->pathFields);
        $payloadShape->addRequired($fieldName, $resultPayloadShape);
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

        // Store required fields for this inline fragment
        $this->storeInlineFragmentRequiredFields($selection, $context->fqcn . '\\' . $className);

        // Create the inline fragment class plan
        $this->createInlineFragmentClassPlan(
            $fragmentType,
            $mergedFields,
            $mergedPayloadShape,
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
        /** @var Type&NamedType $fragmentType */
        $fieldName = lcfirst($selection->name->value);

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

        // Merge fragment payload shape
        $fragmentPayloadShape = $this->fragmentPayloadShapes[$selection->name->value];
        $nakedFragmentPayloadShape = $this->typeMapper->getNakedType($fragmentPayloadShape);
        Assert::isInstanceOf($nakedFragmentPayloadShape, ArrayShapeType::class);

        // Merge fragment spread fields properly using TypeMapper's mergeArrayShape
        $currentPayloadShape = $payloadShape->toArrayShape();
        $mergedPayloadShape = $this->typeMapper->mergeArrayShape($currentPayloadShape, $nakedFragmentPayloadShape);
        Assert::isInstanceOf($mergedPayloadShape, ArrayShapeType::class);

        // Update the existing payload shape with merged values
        foreach ($mergedPayloadShape->getShape() as $key => $value) {
            // ArrayShapeType always returns array{type: Type, optional: bool}
            assert(is_string($key));
            $payloadShape->add($key, $value['type'], $value['optional']);
        }
    }

    // Helper methods

    private function wrapInList(SelectionSetResult $inner, PlanningContext $context) : SelectionSetResult
    {
        $listFields = SymfonyType::list($inner->getFieldsType());
        $listPayloadShape = SymfonyType::list($inner->getPayloadShapeType());

        $resultType = $context->indexByType !== null && $context->indexBy !== []
            ? new IndexByCollectionType($context->indexByType, $inner->resultType, $context->indexBy)
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
     * @return null|array{type: SymfonyType, fields: list<string>}
     */
    private function processIndexByDirective(FieldNode $selection, Type $nakedFieldType) : ?array
    {
        if ( ! $this->config->indexByDirective) {
            return null;
        }

        $indexBy = $this->directiveProcessor->getIndexByDirective($selection->directives);

        if ($indexBy === []) {
            return null;
        }

        return [
            'type' => $this->typeMapper->mapGraphQLTypeToPHPType(RecursiveTypeFinder::find($nakedFieldType, $indexBy)),
            'fields' => $indexBy,
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
        foreach ($selection->selectionSet->selections as $fieldSelection) {
            if ($fieldSelection instanceof FieldNode && $fieldSelection->name->value !== '__typename') {
                $requiredFields[] = $fieldSelection->name->value;
            }
        }

        $this->inlineFragmentRequiredFields[$inlineFragmentKey] = $requiredFields;
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
}
