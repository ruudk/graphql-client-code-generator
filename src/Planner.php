<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Exception;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ExecutableDefinitions;
use GraphQL\Validator\Rules\FieldsOnCorrectType;
use GraphQL\Validator\Rules\FragmentsOnCompositeTypes;
use GraphQL\Validator\Rules\KnownArgumentNames;
use GraphQL\Validator\Rules\KnownArgumentNamesOnDirectives;
use GraphQL\Validator\Rules\KnownDirectives;
use GraphQL\Validator\Rules\KnownTypeNames;
use GraphQL\Validator\Rules\LoneAnonymousOperation;
use GraphQL\Validator\Rules\LoneSchemaDefinition;
use GraphQL\Validator\Rules\NoFragmentCycles;
use GraphQL\Validator\Rules\NoUndefinedVariables;
use GraphQL\Validator\Rules\NoUnusedVariables;
use GraphQL\Validator\Rules\OneOfInputObjectsRule;
use GraphQL\Validator\Rules\OverlappingFieldsCanBeMerged;
use GraphQL\Validator\Rules\PossibleFragmentSpreads;
use GraphQL\Validator\Rules\PossibleTypeExtensions;
use GraphQL\Validator\Rules\ProvidedRequiredArguments;
use GraphQL\Validator\Rules\ProvidedRequiredArgumentsOnDirectives;
use GraphQL\Validator\Rules\ScalarLeafs;
use GraphQL\Validator\Rules\SingleFieldSubscription;
use GraphQL\Validator\Rules\UniqueArgumentDefinitionNames;
use GraphQL\Validator\Rules\UniqueArgumentNames;
use GraphQL\Validator\Rules\UniqueDirectiveNames;
use GraphQL\Validator\Rules\UniqueDirectivesPerLocation;
use GraphQL\Validator\Rules\UniqueEnumValueNames;
use GraphQL\Validator\Rules\UniqueFieldDefinitionNames;
use GraphQL\Validator\Rules\UniqueFragmentNames;
use GraphQL\Validator\Rules\UniqueInputFieldNames;
use GraphQL\Validator\Rules\UniqueOperationNames;
use GraphQL\Validator\Rules\UniqueOperationTypes;
use GraphQL\Validator\Rules\UniqueTypeNames;
use GraphQL\Validator\Rules\UniqueVariableNames;
use GraphQL\Validator\Rules\ValidationRule;
use GraphQL\Validator\Rules\ValuesOfCorrectType;
use GraphQL\Validator\Rules\VariablesAreInputTypes;
use GraphQL\Validator\Rules\VariablesInAllowedPosition;
use JsonException;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQL\DocumentNodeWithSource;
use Ruudk\GraphQLCodeGenerator\GraphQL\FragmentDefinitionNodeWithSource;
use Ruudk\GraphQLCodeGenerator\GraphQL\PossibleTypesFinder;
use Ruudk\GraphQLCodeGenerator\PHP\Visitor\ClassConstantFinder;
use Ruudk\GraphQLCodeGenerator\PHP\Visitor\OperationFinder;
use Ruudk\GraphQLCodeGenerator\Planner\OperationPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\EnumClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\ErrorClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\ExceptionClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\InputClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\NodeNotFoundExceptionPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\OperationClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\PlannerResult;
use Ruudk\GraphQLCodeGenerator\Planner\SelectionSetPlanner;
use Ruudk\GraphQLCodeGenerator\Validator\IndexByValidator;
use Ruudk\GraphQLCodeGenerator\Visitor\DefinedFragmentsVisitor;
use Ruudk\GraphQLCodeGenerator\Visitor\IndexByRemover;
use Ruudk\GraphQLCodeGenerator\Visitor\UsedFragmentsVisitor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final class Planner
{
    private readonly Schema $schema;

    /**
     * @var array<string, array{SymfonyType, SymfonyType}>
     */
    private array $scalars;

    /**
     * @var array<string, SymfonyType>
     */
    private array $inputObjectTypes;

    /**
     * @var array<string, array{SymfonyType, SymfonyType}>
     */
    private array $objectTypes;

    /**
     * @var array<string, SymfonyType>
     */
    private array $enumTypes;

    /**
     * @var array<class-string<ValidationRule>, ValidationRule>
     */
    private array $validatorRules;
    private readonly SchemaLoader $schemaLoader;
    private readonly Optimizer $optimizer;
    private TypeMapper $typeMapper;
    private DirectiveProcessor $directiveProcessor;
    private VariableParser $variableParser;
    private PossibleTypesFinder $possibleTypesFinder;
    private \PhpParser\Parser $phpParser;
    private Filesystem $filesystem;

    /**
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws Exception
     */
    public function __construct(
        private readonly Config $config,
        private readonly EnglishInflector $inflector = new EnglishInflector(),
    ) {
        $this->inputObjectTypes = $config->inputObjectTypes;
        $this->objectTypes = $config->objectTypes;
        $this->enumTypes = $config->enumTypes;

        $this->scalars = [
            'ID' => [SymfonyType::string(), SymfonyType::string()],
            'String' => [SymfonyType::string(), SymfonyType::string()],
            'Int' => [SymfonyType::int(), SymfonyType::int()],
            'Float' => [SymfonyType::float(), SymfonyType::float()],
            'Boolean' => [SymfonyType::bool(), SymfonyType::bool()],
            ...$config->scalars,
        ];

        $this->schemaLoader = new SchemaLoader(new Filesystem());
        $this->schema = $this->schemaLoader->load($config->schema, $config->indexByDirective);
        $this->optimizer = new Optimizer($this->schema);
        $this->possibleTypesFinder = new PossibleTypesFinder($this->schema);

        $this->validatorRules = [
            ExecutableDefinitions::class => new ExecutableDefinitions(),
            UniqueOperationNames::class => new UniqueOperationNames(),
            LoneAnonymousOperation::class => new LoneAnonymousOperation(),
            SingleFieldSubscription::class => new SingleFieldSubscription(),
            KnownTypeNames::class => new KnownTypeNames(),
            FragmentsOnCompositeTypes::class => new FragmentsOnCompositeTypes(),
            VariablesAreInputTypes::class => new VariablesAreInputTypes(),
            ScalarLeafs::class => new ScalarLeafs(),
            FieldsOnCorrectType::class => new FieldsOnCorrectType(),
            UniqueFragmentNames::class => new UniqueFragmentNames(),
            PossibleFragmentSpreads::class => new PossibleFragmentSpreads(),
            NoFragmentCycles::class => new NoFragmentCycles(),
            UniqueVariableNames::class => new UniqueVariableNames(),
            NoUndefinedVariables::class => new NoUndefinedVariables(),
            NoUnusedVariables::class => new NoUnusedVariables(),
            KnownDirectives::class => new KnownDirectives(),
            UniqueDirectivesPerLocation::class => new UniqueDirectivesPerLocation(),
            KnownArgumentNames::class => new KnownArgumentNames(),
            UniqueArgumentNames::class => new UniqueArgumentNames(),
            ValuesOfCorrectType::class => new ValuesOfCorrectType(),
            ProvidedRequiredArguments::class => new ProvidedRequiredArguments(),
            VariablesInAllowedPosition::class => new VariablesInAllowedPosition(),
            OverlappingFieldsCanBeMerged::class => new OverlappingFieldsCanBeMerged(),
            UniqueInputFieldNames::class => new UniqueInputFieldNames(),
            OneOfInputObjectsRule::class => new OneOfInputObjectsRule(),
            LoneSchemaDefinition::class => new LoneSchemaDefinition(),
            UniqueOperationTypes::class => new UniqueOperationTypes(),
            UniqueTypeNames::class => new UniqueTypeNames(),
            UniqueEnumValueNames::class => new UniqueEnumValueNames(),
            UniqueFieldDefinitionNames::class => new UniqueFieldDefinitionNames(),
            UniqueArgumentDefinitionNames::class => new UniqueArgumentDefinitionNames(),
            UniqueDirectiveNames::class => new UniqueDirectiveNames(),
            PossibleTypeExtensions::class => new PossibleTypeExtensions(),
            KnownArgumentNamesOnDirectives::class => new KnownArgumentNamesOnDirectives(),
            ProvidedRequiredArgumentsOnDirectives::class => new ProvidedRequiredArgumentsOnDirectives(),
        ];

        if ($config->indexByDirective) {
            $this->validatorRules[IndexByValidator::class] = new IndexByValidator($this->scalars);
        }

        $this->phpParser = new ParserFactory()->createForNewestSupportedVersion();
        $this->filesystem = new Filesystem();
    }

    /**
     * @throws JsonException
     * @throws \GraphQL\Error\SyntaxError
     * @throws InvariantViolation
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function plan() : PlannerResult
    {
        $finder = Finder::create()->files()
            ->in($this->config->queriesDir)
            ->name('*.graphql')
            ->sortByName();

        if ($this->schemaLoader->schemaPath !== null) {
            $finder->notPath(Path::makeRelative($this->schemaLoader->schemaPath, $this->config->queriesDir));
        }

        $operations = [];
        $usedTypesCollector = new UsedTypesCollector($this->schema);
        $definedFragments = [];

        // First pass: parse all queries to find what types are actually used
        foreach ($finder as $file) {
            $document = Parser::parse($file->getContents());

            $usedTypesCollector->analyze($document);

            foreach (DefinedFragmentsVisitor::getDefinedFragments($document) as $name => $fragmentNode) {
                if (isset($definedFragments[$name])) {
                    throw new Exception(sprintf(
                        'File "%s" defined fragment "%s" but it is already defined in "%s".',
                        $definedFragments[$name],
                        $name,
                        $file->getPathname(),
                    ));
                }

                $definedFragments[$name] = $file->getPathname();
            }

            $operations[$file->getPathname()][] = DocumentNodeWithSource::create(
                $document,
                Path::makeRelative($file->getPathname(), $this->config->projectDir),
            );
        }

        $operationsToInject = [];

        if ($this->config->inlineProcessingDirectories !== []) {
            $finder = Finder::create()
                ->files()
                ->in($this->config->inlineProcessingDirectories)
                ->name('*.php')
                ->contains(GeneratedGraphQLClient::class);

            foreach ($finder as $file) {
                $stmts = $this->phpParser->parse($this->filesystem->readFile($file->getPathname()));
                Assert::notNull($stmts, 'Failed to parse PHP file');

                $classConstants = new ClassConstantFinder();
                $nameResolver = new NameResolver(options: [
                    'replaceNodes' => false,
                ]);
                new NodeTraverser($nameResolver, $classConstants)->traverse($stmts);

                $operationFinder = new OperationFinder(
                    $classConstants->constants,
                    $nameResolver->getNameContext(),
                );
                new NodeTraverser($operationFinder)->traverse($stmts);

                foreach ($operationFinder->operations as $className => $methods) {
                    foreach ($methods as $method => $properties) {
                        foreach ($properties as $property => $operation) {
                            $hash = hash('sha256', implode(',', [
                                $className,
                                $method,
                                $property,
                            ]));
                            $shortHash = substr($hash, 0, 6);

                            $document = Parser::parse($operation);
                            Assert::count($document->definitions, 1);

                            [$definition] = $document->definitions;
                            Assert::isInstanceOf($definition, OperationDefinitionNode::class);

                            $operationType = $definition->operation;
                            $operationName = sprintf('Inline%s', $shortHash);
                            $definition->name = new NameNode([
                                'value' => $operationName,
                            ]);

                            $operationsToInject[$file->getPathname()][$method][$property] = sprintf('%s\%s\%s%s', $this->config->namespace, ucfirst($operationType), $operationName, ucfirst($operationType));
                            $operations[$file->getPathname()][] = DocumentNodeWithSource::create($document, $className);
                        }
                    }
                }
            }
        }

        $usedTypes = $usedTypesCollector->usedTypes;

        // Initialize enum and input types based on usage
        foreach ($this->schema->getTypeMap() as $typeName => $type) {
            if (str_starts_with($typeName, '__')) {
                continue;
            }

            if ( ! in_array($typeName, $usedTypes, true)) {
                continue;
            }

            if ($type instanceof EnumType) {
                $this->enumTypes[$typeName] ??= new BackedEnumType($this->fullyQualified('Enum', $typeName), SymfonyType::string());

                continue;
            }

            if ($type instanceof InputObjectType) {
                $this->inputObjectTypes[$typeName] ??= SymfonyType::object($this->fullyQualified('Input', $typeName));

                continue;
            }
        }

        // Initialize the TypeMapper with the discovered types
        $this->typeMapper = new TypeMapper(
            $this->schema,
            $this->scalars,
            $this->enumTypes,
            $this->inputObjectTypes,
            $this->objectTypes,
        );

        // Initialize helper classes
        $this->directiveProcessor = new DirectiveProcessor();
        $this->variableParser = new VariableParser($this->typeMapper);

        // Create the planner
        $planner = new SelectionSetPlanner(
            $this->config,
            $this->schema,
            $this->typeMapper,
            $this->directiveProcessor,
            $this->inflector,
        );

        $result = new PlannerResult();

        // Plan enum and input types
        foreach ($this->schema->getTypeMap() as $typeName => $type) {
            if (str_starts_with($typeName, '__')) {
                continue;
            }

            if ($type instanceof EnumType) {
                if ( ! in_array($typeName, $usedTypes, true)) {
                    continue;
                }

                if ( ! in_array($typeName, $this->config->ignoreTypes, true)) {
                    $values = [];
                    foreach ($type->getValues() as $value) {
                        Assert::string($value->value, 'Enum value must be a string');
                        $values[$value->name] = [
                            'value' => $value->value,
                            'description' => $value->description,
                        ];
                    }

                    $result->addClass(new EnumClassPlan(
                        path: $this->config->outputDir . '/Enum/' . $typeName . '.php',
                        typeName: $typeName,
                        description: $type->description(),
                        values: $values,
                    ));
                }

                continue;
            }

            if ($type instanceof InputObjectType) {
                if ( ! in_array($typeName, $usedTypes, true)) {
                    continue;
                }

                if (in_array($typeName, $this->config->ignoreTypes, true)) {
                    continue;
                }

                $fields = [];
                foreach ($type->getFields() as $fieldName => $field) {
                    $fields[$fieldName] = [
                        'type' => $this->typeMapper->mapGraphQLTypeToPHPType($field->getType()),
                        'required' => $field->isRequired(),
                        'description' => $field->description,
                    ];
                }

                $result->addClass(new InputClassPlan(
                    path: $this->config->outputDir . '/Input/' . $typeName . '.php',
                    typeName: $typeName,
                    description: $type->description(),
                    isOneOf: $type->isOneOf(),
                    fields: $fields,
                ));

                continue;
            }
        }

        // Plan NodeNotFoundException only if dumpOrThrows is enabled
        if ($this->config->dumpOrThrows) {
            $queryType = $this->schema->getQueryType();
            Assert::notNull($queryType);
            $result->addClass(new NodeNotFoundExceptionPlan(
                path: $this->config->outputDir . '/NodeNotFoundException.php',
            ));
        }

        // Plan fragments
        $ordered = FragmentOrderer::orderFragments($operations);

        // First, set ALL fragments on the planner so they're available when processing
        /**
         * @var list<array{
         *     name: string,
         *     fragment: FragmentDefinitionNodeWithSource,
         *     type: NamedType&Type
         * }> $fragmentsToProcess
         */
        $fragmentsToProcess = [];
        foreach (array_reverse($ordered) as $fragment) {
            $errors = DocumentValidator::validate($this->schema, new DocumentNode([
                'definitions' => new NodeList([$fragment]),
            ]), $this->validatorRules);

            if ($errors !== []) {
                throw new Exception(sprintf('Fragment validation failed: %s', implode(PHP_EOL, array_map(fn($error) => $error->getMessage(), $errors))));
            }

            $fragment = $this->optimizer->optimize($fragment, []);

            $name = $fragment->name->value;

            $type = Type::getNamedType($this->schema->getType($fragment->typeCondition->name->value));

            Assert::notNull($type, 'Fragment type is expected');

            $planner->setFragmentType($name, $type);
            $planner->setFragmentDefinition($name, $fragment, UsedFragmentsVisitor::getUsedFragments($fragment));

            // Store for processing after all are set
            $fragmentsToProcess[] = [
                'name' => $name,
                'fragment' => $fragment,
                'type' => $type,
            ];
        }

        // Now process all fragments (after all have been set on the planner)
        foreach ($fragmentsToProcess as $fragmentData) {
            $name = $fragmentData['name'];
            $fragment = $fragmentData['fragment'];
            $type = $fragmentData['type'];

            $fqcn = $this->fullyQualified('Fragment', $name);
            $planResult = $planner->plan(
                $fragment->source,
                $fragment->selectionSet,
                $type,
                $this->config->outputDir . '/Fragment/' . $name,
                $fqcn,
                'fragment',
                isGeneratingTopLevelFragment: true,
            );

            $planner->setFragmentPayloadShape($name, $planResult->payloadShape);

            // Merge the planner's result into our main result
            foreach ($planResult->plannerResult->classes as $classPlan) {
                $result->addClass($classPlan);
            }

            // Add the fragment class itself
            $path = $this->config->outputDir . '/Fragment/' . $name . '.php';
            $result->addClass(new DataClassPlan(
                $fragment->source,
                $path,
                $fqcn,
                $type,
                $planResult->fields,
                $planResult->payloadShape,
                $this->possibleTypesFinder->find($type),
                $fragment,
                null,
                $planResult->inlineFragmentRequiredFields,
                false,
                true,
            ));
        }

        // Plan operations
        foreach ($operations as $path => $documents) {
            foreach ($documents as $document) {
                $this->planOperation($document, $path, $result, $planner);
            }
        }

        // Set the discovered types in the result
        $result->setDiscoveredEnumTypes($this->enumTypes);
        $result->setDiscoveredInputObjectTypes($this->inputObjectTypes);

        $result->setOperationsToInject($operationsToInject);

        return $result;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws InvariantViolation
     */
    private function planOperation(DocumentNodeWithSource $document, string $path, PlannerResult $result, SelectionSetPlanner $planner) : void
    {
        $document = $this->optimizer->optimize($document, $planner->fragmentDefinitions);

        $errors = DocumentValidator::validate($this->schema, $document, $this->validatorRules);

        if ($errors !== []) {
            throw new Exception(sprintf(
                'Document validation failed: %s',
                implode(PHP_EOL, array_map(fn($error) => $error->getMessage(), $errors)),
            ));
        }

        $operation = null;
        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                $operation = $definition;

                break;
            }
        }

        if ($operation === null) {
            return;
        }

        Assert::notNull($operation->name, 'Expected operation to have a name');

        $operationName = $operation->name->value;
        $operationType = ucfirst($operation->operation);

        if ($this->config->indexByDirective) {
            $document = new IndexByRemover()->__invoke($document);
        }

        $operationDefinition = Printer::doPrint($document);

        $queryClassName = $operationName;
        $queryDir = $this->config->outputDir . '/' . $operationType;
        $operationDir = $queryDir . '/' . $operationName;

        $parsedVariables = $this->variableParser->parseVariables($operation);

        // Build the full variables structure for OperationClassPlan
        /** @var array<non-empty-string, array{required: bool, typeNode: null, type: SymfonyType}> $variables */
        $variables = [];
        foreach ($parsedVariables as $name => $type) {
            /** @var non-empty-string $name */
            $variables[$name] = [
                'required' => ! ($type instanceof SymfonyType\NullableType),
                'typeNode' => null,
                'type' => $type,
            ];
        }

        $rootType = match ($operation->operation) {
            'query' => $this->schema->getQueryType(),
            'mutation' => $this->schema->getMutationType(),
            default => throw new Exception('Only query and mutation operations are supported'),
        };

        Assert::notNull($rootType, 'Expected root type to be defined');

        $fqcn = $this->fullyQualified($operationType, $operationName, 'Data');

        // Plan the data class and its nested classes
        $planResult = $planner->plan(
            $document->source,
            $operation->selectionSet,
            $rootType,
            $operationDir . '/Data',
            $fqcn,
            $operation->operation,
        );

        // Merge the planner's result into our main result
        foreach ($planResult->plannerResult->classes as $classPlan) {
            $result->addClass($classPlan);
        }

        // Create the data class plan
        $dataClassPlan = new DataClassPlan(
            $document->source,
            $operationDir . '/Data.php',
            $fqcn,
            $rootType,
            $planResult->fields,
            $planResult->payloadShape,
            [],
            $operation,
            null,
            $planResult->inlineFragmentRequiredFields,
            true,
            false,
        );
        $result->addClass($dataClassPlan);

        // Create the operation class plan
        $operationClassPlan = new OperationClassPlan(
            path: $queryDir . '/' . $queryClassName . $operationType . '.php',
            fqcn: $this->fullyQualified($operationType, $queryClassName . $operationType),
            operationName: $operationName,
            operationType: $operationType,
            queryClassName: $queryClassName,
            operationDefinition: $operationDefinition,
            variables: $variables,
            relativeFilePath: Path::makeRelative($path, $this->config->projectDir),
            source: $document->source,
        );

        // Create the error class plan
        $errorClassPlan = new ErrorClassPlan(
            path: $operationDir . '/Error.php',
            operationType: $operationType,
            operationName: $operationName,
        );

        // Create the exception class plan only if dumpOrThrows is enabled
        $exceptionClassPlan = null;

        if ($this->config->dumpOrThrows) {
            $exceptionClassPlan = new ExceptionClassPlan(
                path: $operationDir . '/' . $queryClassName . $operationType . 'FailedException.php',
                operationType: $operationType,
                operationName: $operationName,
                exceptionClassName: $queryClassName . $operationType . 'FailedException',
            );
        }

        // Add the operation plan
        $result->addOperation(new OperationPlan(
            operationName: $operationName,
            operationType: $operationType,
            queryClassName: $queryClassName,
            operationDefinition: $operationDefinition,
            variables: $parsedVariables,
            dataClass: $dataClassPlan,
            operationClass: $operationClassPlan,
            errorClass: $errorClassPlan,
            exceptionClass: $exceptionClassPlan,
        ));
    }

    private function fullyQualified(string $part, string ...$moreParts) : string
    {
        if (str_starts_with($part, $this->config->namespace . '\\')) {
            $part = substr($part, strlen($this->config->namespace) + 1);
        }

        return implode('\\', array_filter([$this->config->namespace, $part, ...$moreParts], fn($part) => $part !== ''));
    }
}
