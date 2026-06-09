<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\FieldNode;
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
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\HookDefinition;
use Ruudk\GraphQLCodeGenerator\DirectiveProcessor;
use Ruudk\GraphQLCodeGenerator\GraphQL\AST\InjectedTypenameFieldNode;
use Ruudk\GraphQLCodeGenerator\GraphQL\FragmentDefinitionNodeWithSource;
use Ruudk\GraphQLCodeGenerator\GraphQL\PossibleTypesFinder;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Source\GraphQLFileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\HookInputSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineFragmentSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\TwigFileSource;
use Ruudk\GraphQLCodeGenerator\RecursiveTypeFinder;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\HookPropertyType;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
use Ruudk\GraphQLCodeGenerator\Type\ThrowWhenNullPropertyType;
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
     * @var array<string, array{FragmentDefinitionNodeWithSource, list<string>}> Fragment name to definition mapping
     */
    public private(set) array $fragmentDefinitions = [];

    /**
     * @var array<string, string> Fragment name to fully-qualified class name mapping
     */
    public private(set) array $fragmentFqcns = [];
    private PossibleTypesFinder $possibleTypesFinder;

    public function __construct(
        public private(set) readonly Config $config,
        private readonly Schema $schema,
        private readonly TypeMapper $typeMapper,
        private readonly DirectiveProcessor $directiveProcessor,
        private readonly EnglishInflector $inflector,
        private readonly PlannerResult $result,
    ) {
        $this->possibleTypesFinder = new PossibleTypesFinder($this->schema);
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
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
        SelectionSetNode $selectionSet,
        Type $parent,
        string $outputDirectory,
        string $fqcn,
        string $path,
        ?bool $nullable = null,
        ?array $indexByType = null,
        array $indexBy = [],
    ) : SelectionSetPlanResult {
        $context = new PlanningContext(
            outputDirectory: $outputDirectory,
            fqcn: $fqcn,
            path: $path,
            indexByType: $indexByType,
            indexBy: $indexBy,
        );

        $result = $this->planSelectionSet(
            $source,
            $selectionSet,
            $parent,
            $context,
            $nullable,
        );

        return new SelectionSetPlanResult(
            fields: $result->fields->toArrayShape(),
            fields2: $result->pathFields->all(),
            payloadShape: $result->payloadShape->toArrayShape(),
            type: $result->resultType,
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
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
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
            $innerResult = $this->planSelectionSet(
                $source,
                $selectionSet,
                $wrappedType,
                $innerContext,
                true,
            );

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
            $innerResult = $this->planSelectionSet(
                $source,
                $selectionSet,
                $type->getWrappedType(),
                $context,
                false,
            );

            return $innerResult; // NonNull doesn't change the structure
        }

        if ($type instanceof NullableType && $nullable === null) {
            $innerResult = $this->planSelectionSet(
                $source,
                $selectionSet,
                $type,
                $context,
                true,
            );

            return $this->wrapInNullable($innerResult);
        }

        Assert::isInstanceOf($type, NamedType::class, 'Parent type must be a named type');

        return $this->planNamedTypeSelectionSet(
            $source,
            $selectionSet,
            $type,
            $context,
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws LogicException
     */
    private function planNamedTypeSelectionSet(
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
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
            $this->config->hooks,
        );
        $payloadShape = $payloadShapeBuilder->buildPayloadShape($selectionSet, $type);

        // TODO This should not be part of the GraphQL operation, done by a visitor/optimizer.
        // Check if we need to implicitly add __typename
        if ($this->needsImplicitTypename($selectionSet, $type)) {
            // Only the payload shape needs __typename here so the generated
            // discrimination can read `$this->data['__typename']`;
            // PayloadShapeBuilder already handles that. Deliberately NOT added
            // to $fields: an implicitly required __typename is an internal
            // concern and must not surface as a public property. A
            // user-selected __typename still gets its property via
            // processFieldSelection().
            $pathFields->add('__typename', SymfonyType::string());
        }

        // IMPORTANT: Collect and merge field selections from both direct selections and fragments
        // This ensures that fields selected in both places get the complete payload shape
        $mergedFieldSelections = $this->collectMergedFieldSelections($selectionSet, $type);

        // Process the merged field selections
        foreach ($mergedFieldSelections as $fieldName => $mergedSelection) {
            $this->processFieldSelection(
                $source,
                $mergedSelection,
                $type,
                $context,
                $fields,
                $pathFields,
                $payloadShape,
            );
        }

        // Process inline fragments
        foreach ($selectionSet->selections as $selection) {
            if ( ! $selection instanceof InlineFragmentNode) {
                continue;
            }

            $this->processInlineFragment(
                $source,
                $selection,
                $type,
                $context,
                $fields,
                $pathFields,
                $payloadShape,
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
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
        FieldNode $selection,
        Type $parent,
        PlanningContext $context,
        FieldCollection $fields,
        PathFieldMap $pathFields,
        PayloadShape $payloadShape,
    ) : void {
        $fieldName = $selection->alias->value ?? $selection->name->value;

        // Hook fields are synthetic — they don't exist in the schema and their value comes from
        // a user-supplied callable at runtime rather than from the server response.
        $hookName = $this->directiveProcessor->getHookDirective($selection->directives);

        if ($hookName !== null) {
            Assert::keyExists(
                $this->config->hooks,
                $hookName,
                sprintf('Hook "%s" used in selection is not registered via Config::withHook().', $hookName),
            );

            $hook = $this->config->hooks[$hookName];

            // The @hook field may only sit on a type the hook declares it supports.
            // (The hook's required fields are merged into $payloadShape by
            // PayloadShapeBuilder; they are deliberately kept out of $fields so the
            // caller's typed API is not polluted by the hook's internals.)
            $this->assertHookSiteType($parent, $hook);

            $fields->add($fieldName, new HookPropertyType(
                $hook->name,
                $hook->requiresFqcn,
                $hook->requiresClassName,
                $hook->returnType,
                $hook->batched,
            ));

            return;
        }

        // Handle __typename specially
        if ($fieldName === '__typename') {
            // A __typename the generator injected for runtime type
            // discrimination is an internal concern: it must exist in the
            // payload shape (read via `$this->data['__typename']`) but must
            // NOT surface as a public property. Only a user-selected
            // __typename becomes a property.
            if ( ! $selection instanceof InjectedTypenameFieldNode) {
                $fields->add($fieldName, SymfonyType::string());
            }

            $pathFields->add($fieldName, SymfonyType::string());
            $payloadShape->addRequired($fieldName, SymfonyType::string());

            return;
        }

        Assert::isInstanceOf($parent, HasFieldsType::class, 'Parent type must have fields');

        $fieldType = $parent->getField($selection->name->value)->getType();
        $nakedFieldType = $fieldType instanceof WrappingType
            ? $fieldType->getInnermostType()
            : $fieldType;

        $throwWhenNull = $this->directiveProcessor->hasThrowWhenNullDirective($selection->directives);

        if ($throwWhenNull) {
            Assert::isInstanceOf($parent, NamedType::class);
            Assert::isInstanceOf(
                $fieldType,
                NullableType::class,
                sprintf(
                    '@throwWhenNull on "%s.%s" requires a nullable field, got %s.',
                    $parent->name(),
                    $selection->name->value,
                    $fieldType->toString(),
                ),
            );
        }

        // Handle predefined object types
        if ($nakedFieldType instanceof ObjectType && isset($this->config->objectTypes[$nakedFieldType->name()])) {
            [$objectPayloadShape, $objectType] = $this->config->objectTypes[$nakedFieldType->name()];

            $finalType = $fieldType instanceof NullableType
                ? SymfonyType::nullable($objectType)
                : $objectType;

            $finalPayloadShape = $fieldType instanceof NullableType
                ? SymfonyType::nullable($objectPayloadShape)
                : $objectPayloadShape;

            $fields->add($fieldName, $throwWhenNull ? new ThrowWhenNullPropertyType($objectType) : $finalType);
            $payloadShape->addRequired($fieldName, $finalPayloadShape);

            return;
        }

        // Handle nested selection sets
        if ($selection->selectionSet !== null) {
            $this->processNestedSelection(
                $source,
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

            if ($throwWhenNull) {
                $stored = $fields->get($fieldName);
                $unwrapped = $stored instanceof SymfonyType\NullableType ? $stored->getWrappedType() : $stored;
                $fields->add($fieldName, new ThrowWhenNullPropertyType($unwrapped));
            }

            return;
        }

        // Handle scalar fields
        $mappedType = $this->typeMapper->mapGraphQLTypeToPHPType($fieldType);
        $mappedPayloadType = $this->typeMapper->mapGraphQLTypeToPHPType($fieldType, builtInOnly: true);

        if ($throwWhenNull) {
            $unwrapped = $mappedType instanceof SymfonyType\NullableType ? $mappedType->getWrappedType() : $mappedType;
            $mappedType = new ThrowWhenNullPropertyType($unwrapped);
        }

        $fields->add($fieldName, $mappedType);
        $pathFields->addWithPrefix($context->path, $fieldName, $mappedType);
        $payloadShape->addRequired($fieldName, $mappedPayloadType);
    }

    /**
     * A `@hook` field may only be placed on a selection whose type is the one the
     * hook declares in its `requires` fragment — that exact object type, or (when
     * the hook's condition is an interface) any object type implementing it.
     *
     * @throws InvalidArgumentException
     */
    private function assertHookSiteType(Type $parent, HookDefinition $hook) : void
    {
        $parentName = $parent instanceof NamedType ? $parent->name() : (string) $parent;
        $matches = $parentName === $hook->requiresTypeCondition;

        if ( ! $matches && $parent instanceof ObjectType) {
            foreach ($parent->getInterfaces() as $interface) {
                if ($interface->name() === $hook->requiresTypeCondition) {
                    $matches = true;

                    break;
                }
            }
        }

        Assert::true($matches, sprintf(
            'Hook "%s" requires type "%s" but @hook(name: "%s") is used on a "%s" selection.',
            $hook->name,
            $hook->requiresTypeCondition,
            $hook->name,
            $parentName,
        ));
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws LogicException
     */
    private function processNestedSelection(
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
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
            $source,
            $selection->selectionSet,
            $fieldType,
            $nestedContext,
        );

        // Handle special nodes for connections
        $nodesType = $this->extractNodesType($nakedFieldType, $nestedResult, $context->path . '.' . $fieldName);

        // Create the class plan
        $this->createDataClassPlan(
            $source,
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
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
        InlineFragmentNode $selection,
        Type $parent,
        PlanningContext $context,
        FieldCollection $fields,
        PathFieldMap $pathFields,
        PayloadShape $payloadShape,
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
            $source,
            $selection->selectionSet,
            $fragmentType,
            $fragmentContext,
        );

        // The inline-fragment class only exposes what is selected inside
        // `... on Type { ... }`. Parent fields are deliberately NOT merged in:
        // doing so made it impossible to tell which interface-level fields a
        // given variant actually consumes (over-fetch detection). If a field
        // is needed on the variant it must be selected within the fragment.
        $fragmentSpecificBuilder = new PayloadShapeBuilder(
            $this->schema,
            $this->typeMapper,
            $this->fragmentDefinitions,
            $this->fragmentTypes,
            $this->config->hooks,
        );

        // Using fragmentType as parent ensures fields won't be marked optional.
        $inlineFragmentPayloadShape = $fragmentSpecificBuilder->buildPayloadShape($selection->selectionSet, $fragmentType);

        // Add __typename as it's always present for inline fragments
        if ( ! $inlineFragmentPayloadShape->has('__typename')) {
            $inlineFragmentPayloadShape->addRequired('__typename', new StringLiteralType($fragmentType->name()));
        }

        // Store required fields for this inline fragment
        $this->storeInlineFragmentRequiredFields($selection, $context->fqcn . '\\' . $className);

        // Create the inline fragment class plan with the correct payload shape
        $this->createInlineFragmentClassPlan(
            $source,
            $fragmentType,
            $fragmentResult->fields,
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
     * @throws \InvalidArgumentException
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
            $this->fragmentFqcns[$selection->name->value] ?? $this->fullyQualified('Fragment', $selection->name->value),
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
                $fragmentDef = $this->fragmentDefinitions[$selection->name->value][0];
                // Collect all fields from the fragment's selection set
                $this->collectRequiredFieldsFromSelectionSet($fragmentDef->selectionSet, $requiredFields);
            }

            // Store with the fragment's class name as key
            if ($requiredFields !== []) {
                $this->inlineFragmentRequiredFields[$fragmentObjectType->getClassName()] = $requiredFields;
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
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
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
        $fieldIsList = $fields instanceof SymfonyType\CollectionType && $fields->isList();

        if ($fieldIsList) {
            $fields = $fields->getCollectionValueType();
        }

        if ($payloadShape instanceof SymfonyType\CollectionType && $payloadShape->isList()) {
            $payloadShape = $payloadShape->getCollectionValueType();
        }

        $dataClass = new DataClassPlan(
            source: $source,
            path: $context->outputDirectory . '.php',
            fqcn: $context->fqcn,
            parentType: $parentType,
            fields: $fields,
            payloadShape: $payloadShape,
            possibleTypes: $this->possibleTypesFinder->find($fieldType),
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
            markTypenameAsApi: $this->shouldMarkTypenameAsApi($selection),
        );

        $this->result->addClass($dataClass);
    }

    /**
     * Selecting nothing but `__typename` means the caller does not actually
     * read the value back — GraphQL just forces at least one field to be
     * selected. The generated `__typename` property is then never used, so we
     * tag it `@api` to stop dead-code analysis from flagging it. This holds
     * for every sole-`__typename` selection (probing an object's
     * presence/non-null without reading any data), so it must NOT trigger
     * only when other fields are selected alongside `__typename`.
     */
    private function shouldMarkTypenameAsApi(FieldNode $selection) : bool
    {
        return $this->selectionSetIsSoleTypename($selection->selectionSet);
    }

    /**
     * True when the only thing selected is an explicitly queried
     * `__typename`. Selecting nothing else means the caller does not read
     * the value back (GraphQL just forces at least one field), so the
     * generated `__typename` property is never used.
     */
    private function selectionSetIsSoleTypename(?SelectionSetNode $selectionSet) : bool
    {
        $selections = $selectionSet?->selections;

        if ($selections === null || count($selections) !== 1) {
            return false;
        }

        $only = $selections[0];

        return $only instanceof FieldNode && $only->name->value === '__typename';
    }

    private function createInlineFragmentClassPlan(
        GraphQLFileSource | HookInputSource | InlineFragmentSource | InlineSource | TwigFileSource $source,
        NamedType & Type $fragmentType,
        FieldCollection $fields,
        PayloadShape $payloadShape,
        PlanningContext $context,
        InlineFragmentNode $selection,
    ) : void {
        $dataClass = new DataClassPlan(
            source: $source,
            path: $context->outputDirectory . '.php',
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
            markTypenameAsApi: $this->selectionSetIsSoleTypename($selection->selectionSet),
        );

        $this->result->addClass($dataClass);
    }

    /**
     * @throws \InvalidArgumentException
     */
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
     * @throws \InvalidArgumentException
     */
    private function collectRequiredFieldsFromSelectionSet(
        SelectionSetNode $selectionSet,
        array &$requiredFields,
    ) : void {
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode && $selection->name->value !== '__typename') {
                // The synthetic hook field itself is never present in the raw response, so it
                // cannot guard the variant. But the data the hook `requires` IS injected into
                // the operation and returned by the server, so those fields must guard the
                // variant — otherwise the parent's optional payload offsets are never narrowed
                // for the leaf class that consumes them.
                $hookName = $this->directiveProcessor->getHookDirective($selection->directives);

                if ($hookName !== null) {
                    $hook = $this->config->hooks[$hookName] ?? null;

                    if ($hook !== null) {
                        $this->collectRequiredFieldsFromSelectionSet($hook->requiresFragment->selectionSet, $requiredFields);
                    }

                    continue;
                }

                $fieldName = $selection->alias->value ?? $selection->name->value;

                if ( ! in_array($fieldName, $requiredFields, true)) {
                    $requiredFields[] = $fieldName;
                }
            } elseif ($selection instanceof FragmentSpreadNode) {
                // Fragment spreads also contribute required fields
                $fragmentName = $selection->name->value;

                if (isset($this->fragmentDefinitions[$fragmentName])) {
                    $this->collectRequiredFieldsFromSelectionSet(
                        $this->fragmentDefinitions[$fragmentName][0]->selectionSet,
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

    public function setFragmentPayloadShape(string $name, SymfonyType $shape) : void
    {
        $this->fragmentPayloadShapes[$name] = $shape;
    }

    public function setFragmentType(string $name, NamedType & Type $type) : void
    {
        $this->fragmentTypes[$name] = $type;
    }

    public function setFragmentFqcn(string $name, string $fqcn) : void
    {
        $this->fragmentFqcns[$name] = $fqcn;
    }

    /**
     * @param list<string> $dependencies
     */
    public function setFragmentDefinition(string $name, FragmentDefinitionNodeWithSource $definition, array $dependencies) : void
    {
        $this->fragmentDefinitions[$name] = [$definition, $dependencies];
    }
}
