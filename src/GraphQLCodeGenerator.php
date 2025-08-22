<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Exception;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\NullableType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildClientSchema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaExtender;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\ExecutableDefinitions;
use GraphQL\Validator\Rules\FieldsOnCorrectType;
use GraphQL\Validator\Rules\FragmentsOnCompositeTypes;
use GraphQL\Validator\Rules\KnownArgumentNames;
use GraphQL\Validator\Rules\KnownArgumentNamesOnDirectives;
use GraphQL\Validator\Rules\KnownDirectives;
use GraphQL\Validator\Rules\KnownFragmentNames;
use GraphQL\Validator\Rules\KnownTypeNames;
use GraphQL\Validator\Rules\LoneAnonymousOperation;
use GraphQL\Validator\Rules\LoneSchemaDefinition;
use GraphQL\Validator\Rules\NoFragmentCycles;
use GraphQL\Validator\Rules\NoUndefinedVariables;
use GraphQL\Validator\Rules\NoUnusedFragments;
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
use ReflectionException;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\DataClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\EnumTypeGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\ErrorClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\ExceptionClassGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\InputTypeGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\NodeNotFoundExceptionGenerator;
use Ruudk\GraphQLCodeGenerator\Generator\OperationClassGenerator;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\BackedEnumTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\CollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\NullableTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ObjectTypeInitializer;
use Ruudk\GraphQLCodeGenerator\Validator\IndexByValidator;
use Ruudk\GraphQLCodeGenerator\Visitor\IndexByRemover;
use function str_replace as str_replace1;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * @phpstan-import-type CodeLines from CodeGenerator
 */
final class GraphQLCodeGenerator
{
    private readonly Schema $schema;

    /**
     * @var array<string, string> Map of relative path to file content
     */
    private array $files = [];

    /**
     * @var array<string, SymfonyType>
     */
    private array $fragmentPayloadShapes = [];

    /**
     * @var array<string, Type&NamedType>
     */
    private array $fragmentTypes = [];

    /**
     * @var array<string, list<string>>
     */
    private array $inlineFragmentRequiredFields = [];

    /**
     * @var array<string, SymfonyType|array{SymfonyType, SymfonyType}>
     */
    private array $scalars;
    private DelegatingTypeInitializer $typeInitializer;
    private ?string $schemaPath = null;
    private Optimizer $optimizer;
    private readonly Config $config;
    private readonly NodeNotFoundExceptionGenerator $nodeNotFoundExceptionGenerator;
    private readonly ErrorClassGenerator $errorClassGenerator;
    private readonly ExceptionClassGenerator $exceptionClassGenerator;
    private readonly EnumTypeGenerator $enumTypeGenerator;
    private readonly OperationClassGenerator $operationClassGenerator;
    private readonly DataClassGenerator $dataClassGenerator;
    private readonly InputTypeGenerator $inputTypeGenerator;

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
    public private(set) array $validatorRules;

    /**
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(
        Config $config,
        private readonly EnglishInflector $inflector = new EnglishInflector(),
    ) {
        $this->config = $config;
        $this->inputObjectTypes = $config->inputObjectTypes;
        $this->objectTypes = $config->objectTypes;
        $this->enumTypes = $config->enumTypes;
        $this->typeInitializer = new DelegatingTypeInitializer(
            new NullableTypeInitializer(),
            new CollectionTypeInitializer(),
            new BackedEnumTypeInitializer($config->addUnknownCaseToEnums, $config->namespace),
            new ObjectTypeInitializer(),
            ...$config->typeInitializers,
        );

        $schema = $config->schema;

        $filesystem = new Filesystem();

        if (is_string($schema) && str_ends_with($schema, '.graphql')) {
            $this->schemaPath = $schema;
            $schema = BuildSchema::build($filesystem->readFile($schema));
        } elseif (is_string($schema) && str_ends_with($schema, '.json')) {
            $this->schemaPath = $schema;
            $introspection = json_decode($filesystem->readFile($schema), true, flags: JSON_THROW_ON_ERROR);

            Assert::isArray($introspection, 'Expected introspection to be an array');
            Assert::keyExists($introspection, 'data', 'Expected introspection to have a "data" key');
            Assert::isArray($introspection['data'], 'Expected introspection data to be an array');

            // @phpstan-ignore argument.type (expects array<string, mixed>, array<mixed, mixed> given)
            $schema = BuildClientSchema::build($introspection['data']);
        }

        Assert::isInstanceOf($schema, Schema::class, 'Invalid schema given, expected .graphql or .json file or Schema instance');

        if ($config->indexByDirective) {
            $schema = SchemaExtender::extend(
                $schema,
                Parser::parse(
                    <<<'GRAPHQL'
                        directive @indexBy(field: String!) on FIELD
                        GRAPHQL
                ),
            );
        }

        $this->schema = $schema;

        $this->scalars = [
            'ID' => SymfonyType::string(),
            'String' => SymfonyType::string(),
            'Int' => SymfonyType::int(),
            'Float' => SymfonyType::float(),
            'Boolean' => SymfonyType::bool(),

            ...$config->scalars,
        ];

        $this->optimizer = new Optimizer($this->schema);

        // Initialize generators
        $this->nodeNotFoundExceptionGenerator = new NodeNotFoundExceptionGenerator($config);
        $this->errorClassGenerator = new ErrorClassGenerator($config);
        $this->exceptionClassGenerator = new ExceptionClassGenerator($config);
        $this->enumTypeGenerator = new EnumTypeGenerator($config);
        $this->operationClassGenerator = new OperationClassGenerator($config);
        $this->dataClassGenerator = new DataClassGenerator($config);
        $this->inputTypeGenerator = new InputTypeGenerator($config);

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
            // KnownFragmentNames::class => new KnownFragmentNames(),
            // NoUnusedFragments::class => new NoUnusedFragments(),
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
    }

    /**
     * @throws JsonException
     * @throws \GraphQL\Error\SyntaxError
     * @throws InvariantViolation
     * @throws IOException
     * @throws Exception
     * @throws InvalidArgumentException
     * @return array<string, string> Map of relative path to file content
     */
    public function generate() : array
    {
        // Reset files at the start
        $this->files = [];

        $finder = new Finder();
        $finder->files()
            ->in($this->config->queriesDir)
            ->name('*.graphql')
            ->sortByName();

        if ($this->schemaPath !== null) {
            $finder->notPath(Path::makeRelative($this->schemaPath, $this->config->queriesDir));
        }

        $operations = [];
        $usedTypesCollector = new UsedTypesCollector($this->schema);

        // First pass: parse all queries to find what types are actually used
        foreach ($finder as $file) {
            $document = Parser::parse($file->getContents());

            $usedTypesCollector->analyze($document);

            $operations[Path::makeRelative($file->getPathname(), $this->config->projectDir)] = $document;
        }

        $usedTypes = $usedTypesCollector->usedTypes;

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

        foreach ($this->schema->getTypeMap() as $typeName => $type) {
            if (str_starts_with($typeName, '__')) {
                continue;
            }

            if ($type instanceof EnumType) {
                if ( ! in_array($typeName, $usedTypes, true)) {
                    continue;
                }

                if ( ! in_array($typeName, $this->config->ignoreTypes, true)) {
                    $this->files['Enum/' . $typeName . '.php'] = $this->enumTypeGenerator->generate($typeName, $type);
                }

                continue;
            }

            if ($type instanceof InputObjectType) {
                if ( ! in_array($typeName, $usedTypes, true)) {
                    continue;
                }

                $this->files['Input/' . $typeName . '.php'] = $this->inputTypeGenerator->generate(
                    $type,
                    $type->isOneOf(),
                    fn($t) => $this->mapGraphQLTypeToPHPType($t),
                );

                continue;
            }
        }

        $this->files['NodeNotFoundException.php'] = $this->nodeNotFoundExceptionGenerator->generate();

        $ordered = FragmentOrderer::orderFragments($operations);
        foreach (array_reverse($ordered) as $fragment) {
            $errors = DocumentValidator::validate($this->schema, new DocumentNode([
                'definitions' => new NodeList([$fragment]),
            ]), $this->validatorRules);

            if ($errors !== []) {
                throw new Exception(sprintf('Fragment validation failed: %s', implode(PHP_EOL, array_map(fn($error) => $error->getMessage(), $errors))));
            }

            $fragment = $this->optimizer->optimize($fragment);

            $name = $fragment->name->value;

            $type = Type::getNamedType($this->schema->getType($fragment->typeCondition->name->value));

            Assert::notNull($type, 'Fragment type is expected');

            $this->fragmentTypes[$name] = $type;

            $fqcn = $this->fullyQualified('Fragment', $name);
            [$fields, $fields2, $payloadShape] = $this->parseSelectionSet(
                $this->config->outputDir . '/Fragment/' . $name,
                $fragment->selectionSet,
                $type,
                $fqcn,
                'fragment',
            );

            $this->fragmentPayloadShapes[$name] = $payloadShape;

            $relativePath = str_replace($this->config->outputDir . '/', '', $this->config->outputDir . '/Fragment/' . $name . '.php');
            $this->files[$relativePath] = $this->dataClassGenerator->generate(
                $type,
                $fields,
                $payloadShape,
                $this->getPossibleTypes($type),
                $fqcn,
                false,
                true,
                $fragment,
                null,
                $this->typeInitializer,
                $this->inlineFragmentRequiredFields,
            );
        }

        foreach ($operations as $relativeFilePath => $document) {
            $this->processOperation($document, $relativeFilePath);
        }

        return $this->files;
    }

    /**
     * @throws IOException
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws InvariantViolation
     */
    private function processOperation(DocumentNode $document, string $relativeFilePath) : void
    {
        $document = $this->optimizer->optimize($document);

        $errors = DocumentValidator::validate($this->schema, $document, $this->validatorRules);

        if ($errors !== []) {
            throw new Exception(sprintf('Document validation failed: %s', implode(PHP_EOL, array_map(fn($error) => $error->getMessage(), $errors))));
        }

        $operation = null;
        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                $operation = $definition;

                break;
            }
        }

        Assert::notNull($operation, 'Expected operation to be defined');
        Assert::notNull($operation->name, 'Expected operation to have a name');

        $operationName = $operation->name->value;
        $operationType = ucfirst($operation->operation);

        $document = new IndexByRemover()->__invoke($document);

        $operationDefinition = Printer::doPrint($document);

        $queryClassName = $operationName;
        $queryDir = $this->config->outputDir . '/' . $operationType;
        $operationDir = $queryDir . '/' . $operationName;

        $variables = $this->parseVariables($operation);

        $relativePath = str_replace($this->config->outputDir . '/', '', $queryDir . '/' . $queryClassName . $operationType . '.php');
        $this->files[$relativePath] = $this->operationClassGenerator->generate(
            $operationName,
            $operationType,
            $queryClassName,
            $operationDefinition,
            $variables,
            $relativeFilePath,
        );

        $rootType = match ($operation->operation) {
            'query' => $this->schema->getQueryType(),
            'mutation' => $this->schema->getMutationType(),
            default => throw new Exception('Only query and mutation operations are supported'),
        };

        Assert::notNull($rootType, 'Expected root type to be defined');

        $fqcn = $this->fullyQualified($operationType, $operationName, 'Data');
        [$fields, $fields2,  $payloadShape] = $this->parseSelectionSet(
            $operationDir . '/Data',
            $operation->selectionSet,
            $rootType,
            $fqcn,
            $operation->operation,
        );
        $relativePath = str_replace($this->config->outputDir . '/', '', $operationDir . '/Data.php');
        $this->files[$relativePath] = $this->dataClassGenerator->generate(
            $rootType,
            $fields,
            $payloadShape,
            [],
            $fqcn,
            true,
            false,
            $operation,
            null,
            $this->typeInitializer,
            $this->inlineFragmentRequiredFields,
        );

        $relativePath = \str_replace($this->config->outputDir . '/', '', $operationDir . '/Error.php');
        $this->files[$relativePath] = $this->errorClassGenerator->generate($operationType, $operationName);

        $relativePath1 = str_replace1(
            $this->config->outputDir . '/',
            '',
            $operationDir . '/' . $queryClassName . $operationType . 'FailedException.php',
        );
        $this->files[$relativePath1] = $this->exceptionClassGenerator->generate(
            $operationType,
            $operationName,
            $queryClassName . $operationType . 'FailedException',
        );
    }

    /**
     * @return array<string, SymfonyType>
     */
    private function parseVariables(OperationDefinitionNode $operation) : array
    {
        $required = [];
        $optional = [];

        foreach ($operation->variableDefinitions as $varDef) {
            $name = $varDef->variable->name->value;
            $type = $this->mapGraphQLASTTypeToPHPType($varDef->type);

            if ($type instanceof SymfonyType\NullableType) {
                $optional[$name] = $type;

                continue;
            }

            $required[$name] = $type;
        }

        return [
            ...$required,
            ...$optional,
        ];
    }

    private function mapGraphQLASTTypeToPHPType(TypeNode $type, ?bool $nullable = null) : SymfonyType
    {
        if ($type instanceof NonNullTypeNode) {
            return $this->mapGraphQLASTTypeToPHPType($type->type, false);
        }

        if ($nullable === null) {
            return SymfonyType::nullable($this->mapGraphQLASTTypeToPHPType($type, true));
        }

        if ($type instanceof ListTypeNode) {
            return SymfonyType::list($this->mapGraphQLASTTypeToPHPType($type->type));
        }

        if ($type instanceof NamedTypeNode) {
            if (isset($this->scalars[$type->name->value])) {
                $scalar = $this->scalars[$type->name->value];

                if ($scalar instanceof SymfonyType) {
                    return $scalar;
                }

                return $scalar[1];
            }

            if (isset($this->enumTypes[$type->name->value])) {
                return $this->enumTypes[$type->name->value];
            }

            if (isset($this->inputObjectTypes[$type->name->value])) {
                return $this->inputObjectTypes[$type->name->value];
            }

            if (isset($this->objectTypes[$type->name->value])) {
                return $this->objectTypes[$type->name->value][1];
            }
        }

        return SymfonyType::mixed();
    }

    private function mapGraphQLTypeToPHPType(Type $type, ?bool $nullable = null, bool $builtInOnly = false) : SymfonyType
    {
        if ($type instanceof NonNull) {
            return $this->mapGraphQLTypeToPHPType($type->getWrappedType(), false, $builtInOnly);
        }

        if ($nullable === null) {
            return SymfonyType::nullable($this->mapGraphQLTypeToPHPType($type, true, $builtInOnly));
        }

        if ($type instanceof ListOfType) {
            return SymfonyType::list($this->mapGraphQLTypeToPHPType($type->getWrappedType(), $builtInOnly));
        }

        if ($type instanceof ScalarType) {
            if (isset($this->scalars[$type->name()])) {
                $scalar = $this->scalars[$type->name()];

                if ($scalar instanceof SymfonyType) {
                    return $scalar;
                }

                return $builtInOnly ? $scalar[0] : $scalar[1];
            }
        }

        if ($type instanceof EnumType && $builtInOnly) {
            return SymfonyType::string();
        }

        if ( ! $builtInOnly && $type instanceof NamedType) {
            if (isset($this->enumTypes[$type->name()])) {
                return $this->enumTypes[$type->name()];
            }

            if (isset($this->inputObjectTypes[$type->name()])) {
                return $this->inputObjectTypes[$type->name()];
            }

            if (isset($this->objectTypes[$type->name()])) {
                return $this->objectTypes[$type->name()][1];
            }
        }

        return SymfonyType::mixed();
    }

    /**
     * @param list<string> $indexBy
     *
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     * @throws IOException
     * @return array{SymfonyType, array<string, SymfonyType>, SymfonyType, SymfonyType}
     */
    private function parseSelectionSet(
        string $outputDirectory,
        SelectionSetNode $selectionSet,
        Type $parent,
        string $fqcn,
        string $path,
        ?bool $nullable = null,
        ?SymfonyType $indexByType = null,
        array $indexBy = [],
    ) : array {
        if ($parent instanceof ListOfType) {
            [$fields, $fields2, $payloadShape, $type] = $this->parseSelectionSet(
                $outputDirectory,
                $selectionSet,
                $parent->getWrappedType(),
                $fqcn,
                $path . '.*',
                true,
            );

            return [
                SymfonyType::list($fields),
                $fields2,
                SymfonyType::list($payloadShape),
                $indexByType !== null && $indexBy !== [] ? new IndexByCollectionType($indexByType, $type, $indexBy) : SymfonyType::list($type),
            ];
        }

        if ($parent instanceof NonNull) {
            return $this->parseSelectionSet(
                $outputDirectory,
                $selectionSet,
                $parent->getWrappedType(),
                $fqcn,
                $path,
                false,
                $indexByType,
                $indexBy,
            );
        }

        if ($parent instanceof NullableType && $nullable === null) {
            [$fields, $fields2, $payloadShape, $type] = $this->parseSelectionSet(
                $outputDirectory,
                $selectionSet,
                $parent,
                $fqcn,
                $path,
                true,
            );

            return [
                SymfonyType::nullable($fields),
                $fields2,
                SymfonyType::nullable($payloadShape),
                SymfonyType::nullable($type),
            ];
        }

        Assert::isInstanceOf($parent, NamedType::class, 'Parent type must be a named type');

        $fields = [];
        $fields2 = [];
        $payloadShape = [];

        // Check if we need to implicitly add __typename
        // Only add it if there are inline fragments in this selection set
        $hasInlineFragments = false;
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof InlineFragmentNode) {
                $hasInlineFragments = true;

                break;
            }
        }

        // Implicitly add __typename for interfaces and unions when there are inline fragments
        if (($parent instanceof InterfaceType || $parent instanceof UnionType) && $hasInlineFragments) {
            $fields['__typename'] = SymfonyType::string();
            $fields2['__typename'] = SymfonyType::string();
            $payloadShape['__typename'] = SymfonyType::string();
        }

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fieldName = $selection->alias->value ?? $selection->name->value;

                if ($fieldName === '__typename') {
                    $fields[$fieldName] = SymfonyType::string();
                    $fields2[$fieldName] = SymfonyType::string();
                    $payloadShape[$fieldName] = SymfonyType::string();

                    continue;
                }

                Assert::isInstanceOf($parent, HasFieldsType::class, 'Parent type must have fields when parsing selection set');

                $fieldType = $parent->getField($selection->name->value)->getType();

                $nakedFieldType = $fieldType;

                if ($fieldType instanceof WrappingType) {
                    $nakedFieldType = $fieldType->getInnermostType();
                }

                if ($nakedFieldType instanceof ObjectType && isset($this->objectTypes[$nakedFieldType->name()])) {
                    [$objectPayloadShape, $objectType] = $this->objectTypes[$nakedFieldType->name()];

                    $fields[$fieldName] = $objectType;
                    $payloadShape[$fieldName] = $objectPayloadShape;

                    if ($fieldType instanceof NullableType) {
                        $fields[$fieldName] = SymfonyType::nullable($fields[$fieldName]);
                        $payloadShape[$fieldName] = SymfonyType::nullable($payloadShape[$fieldName]);
                    }

                    continue;
                }

                if ($selection->selectionSet !== null) {
                    $indexByType = null;
                    $indexBy = [];

                    if ($this->config->indexByDirective) {
                        $indexBy = $this->getIndexByDirective($selection->directives);

                        if ($indexBy !== []) {
                            $indexByType = $this->mapGraphQLTypeToPHPType(RecursiveTypeFinder::find($nakedFieldType, $indexBy));
                        }
                    }

                    $className = ucfirst($this->isList($fieldType) ? $this->singularize($fieldName) : $fieldName);

                    Assert::isInstanceOf($nakedFieldType, NamedType::class, 'Field type must be a named type');

                    if ($this->config->useNodeNameForEdgeNodes && $fieldName === 'node' && str_ends_with($parent->name(), 'Edge')) {
                        $className = ucfirst($nakedFieldType->name());
                    } elseif ($this->config->useConnectionNameForConnections && str_ends_with($nakedFieldType->name(), 'Connection')) {
                        $className = ucfirst($nakedFieldType->name());
                    } elseif ($this->config->useEdgeNameForEdges && str_ends_with($nakedFieldType->name(), 'Edge')) {
                        $className = ucfirst($nakedFieldType->name());
                    }

                    [$subFields, $subFields2, $subPayloadShape, $subType] = $this->parseSelectionSet(
                        $outputDirectory . '/' . $className,
                        $selection->selectionSet,
                        $fieldType,
                        $fqcn . '\\' . $className,
                        $path . '.' . $fieldName,
                        indexByType: $indexByType,
                        indexBy: $indexBy,
                    );

                    $nodesType = null;

                    if ($this->config->addNodesOnConnections && str_ends_with($nakedFieldType->name(), 'Connection')) {
                        $edges = $subFields2[$path . '.' . $fieldName . '.edges'];

                        if ($edges instanceof IndexByCollectionType) {
                            $nodesType = SymfonyType::array(
                                $subFields2[$path . '.' . $fieldName . '.edges.*.node'],
                                $edges->key,
                            );
                        } else {
                            $nodesType = SymfonyType::list($subFields2[$path . '.' . $fieldName . '.edges.*.node']);
                        }
                    }

                    $relativePath = str_replace($this->config->outputDir . '/', '', $outputDirectory . '/' . $className . '.php');
                    $this->files[$relativePath] = $this->dataClassGenerator->generate(
                        $nakedFieldType,
                        $subFields instanceof SymfonyType\CollectionType && $subFields->isList() ? $subFields->getCollectionValueType() : $subFields,
                        $subPayloadShape instanceof SymfonyType\CollectionType && $subPayloadShape->isList() ? $subPayloadShape->getCollectionValueType() : $subPayloadShape,
                        $this->getPossibleTypes($fieldType),
                        $fqcn . '\\' . $className,
                        false,
                        false,
                        new InlineFragmentNode([
                            'typeCondition' => new NamedTypeNode([
                                'name' => new NameNode([
                                    'value' => $nakedFieldType->name(),
                                ]),
                            ]),
                            'selectionSet' => $selection->selectionSet,
                        ]),
                        $nodesType,
                        $this->typeInitializer,
                        $this->inlineFragmentRequiredFields,
                    );

                    if ($this->hasIncludeOrSkipDirective($selection->directives)) {
                        $subType = SymfonyType::nullable($subType);
                        $subPayloadShape = SymfonyType::nullable($subPayloadShape);
                    }

                    $fields[$fieldName] = $subType;
                    $fields2[$path . '.' . $fieldName] = $subType;
                    $fields2 = [...$fields2, ...$subFields2];
                    $payloadShape[$fieldName] = $subPayloadShape;

                    continue;
                }

                $fields[$fieldName] = $this->mapGraphQLTypeToPHPType($fieldType);
                $fields2[$path . '.' . $fieldName] = $this->mapGraphQLTypeToPHPType($fieldType);
                $payloadShape[$fieldName] = $this->mapGraphQLTypeToPHPType($fieldType, builtInOnly: true);

                continue;
            }
        }

        $fieldsBeforeInlineFragments = $fields;
        $payloadShapeBeforeInlineFragments = $payloadShape;

        foreach ($selectionSet->selections as $selection) {
            if ( ! $selection instanceof InlineFragmentNode) {
                continue;
            }

            // TODO
            Assert::notNull($selection->typeCondition, 'Inline fragment must have a type condition for now');

            $fieldType = Type::getNamedType($this->schema->getType($selection->typeCondition->name->value));

            Assert::isInstanceOf($fieldType, NamedType::class, 'Type condition must be a named type');

            $className = sprintf('As%s', $fieldType->name());
            $fieldName = sprintf('as%s', $fieldType->name());

            [$subFields, $subFields2, $subPayloadShape] = $this->parseSelectionSet(
                $outputDirectory . '/' . $className,
                $selection->selectionSet,
                $fieldType,
                $fqcn . '\\' . $className,
                $path . '.' . $fieldName,
            );

            $subFields = $this->mergeArrayShape(SymfonyType::arrayShape($fieldsBeforeInlineFragments), $subFields);
            $subPayloadShape = $this->mergeArrayShape(SymfonyType::arrayShape($payloadShapeBeforeInlineFragments), $subPayloadShape);

            // Store the required fields for this inline fragment
            // Extract field names directly from the inline fragment's selection set
            $inlineFragmentKey = $this->fullyQualified($fqcn, $className);
            $requiredFields = [];
            foreach ($selection->selectionSet->selections as $fieldSelection) {
                if ($fieldSelection instanceof FieldNode && $fieldSelection->name->value !== '__typename') {
                    $requiredFields[] = $fieldSelection->name->value;
                }
            }

            $this->inlineFragmentRequiredFields[$inlineFragmentKey] = $requiredFields;

            $relativePath = str_replace($this->config->outputDir . '/', '', $outputDirectory . '/' . $className . '.php');
            $this->files[$relativePath] = $this->dataClassGenerator->generate(
                $fieldType,
                $subFields instanceof SymfonyType\CollectionType && $subFields->isList() ? $subFields->getCollectionValueType() : $subFields,
                $subPayloadShape instanceof SymfonyType\CollectionType && $subPayloadShape->isList() ? $subPayloadShape->getCollectionValueType() : $subPayloadShape,
                [$fieldType->name()],
                $this->fullyQualified($fqcn, $className),
                false,
                true,
                $selection,
                null,
                $this->typeInitializer,
                $this->inlineFragmentRequiredFields,
            );

            $fields[$fieldName] = new FragmentObjectType(
                $this->fullyQualified($fqcn, $className),
                $fieldType->name(),
                $fieldType,
            );
            $fields2[$path . '.' . $fieldName] = $fields[$fieldName];
            $fields2 = [...$fields2, ...$subFields2];

            if ( ! $parent instanceof ObjectType) {
                $fields[$fieldName] = SymfonyType::nullable($fields[$fieldName]);
                $fields2[$path . '.' . $fieldName] = SymfonyType::nullable($fields2[$path . '.' . $fieldName]);
            }

            // Merge inline fragment payload fields into parent as optional
            $nakedSubPayloadShape = $this->getNakedType($subPayloadShape);
            Assert::isInstanceOf($nakedSubPayloadShape, ArrayShapeType::class, 'Payload shape must be an array shape');

            foreach ($nakedSubPayloadShape->getShape() as $key => ['type' => $type]) {
                if (isset($payloadShape[$key])) {
                    continue;
                }

                $payloadShape[$key] = [
                    'type' => $type,
                    'optional' => true,
                ];
            }
        }

        foreach ($selectionSet->selections as $selection) {
            if ( ! $selection instanceof FragmentSpreadNode) {
                continue;
            }

            $fragmentType = $this->fragmentTypes[$selection->name->value];

            $fieldName = lcfirst($selection->name->value);
            $fields[$fieldName] = new FragmentObjectType(
                $this->fullyQualified('Fragment', $selection->name->value),
                $selection->name->value,
                $fragmentType,
            );
            $fields2[$path . '.' . $fieldName] = $fields[$fieldName];

            if ($parent instanceof InterfaceType || $parent instanceof UnionType) {
                $fields[$fieldName] = SymfonyType::nullable($fields[$fieldName]);
                $fields2[$path . '.' . $fieldName] = SymfonyType::nullable($fields2[$path . '.' . $fieldName]);
            }

            $nakedFragmentPayloadShape = $this->getNakedType($this->fragmentPayloadShapes[$selection->name->value]);
            Assert::isInstanceOf($nakedFragmentPayloadShape, ArrayShapeType::class, 'Fragment shape must be an array shape');

            // Merge fragment spread fields properly
            $currentPayloadShape = SymfonyType::arrayShape($payloadShape);
            $mergedPayloadShape = $this->mergeArrayShape($currentPayloadShape, $nakedFragmentPayloadShape);
            Assert::isInstanceOf($mergedPayloadShape, ArrayShapeType::class);
            $payloadShape = $mergedPayloadShape->getShape();
        }

        return [
            SymfonyType::arrayShape($fields),
            $fields2,
            SymfonyType::arrayShape($payloadShape),
            SymfonyType::object($fqcn),
        ];
    }

    private function isList(Type $fieldType) : bool
    {
        if ($fieldType instanceof NonNull) {
            return $this->isList($fieldType->getWrappedType());
        }

        return $fieldType instanceof ListOfType;
    }

    private function fullyQualified(string $part, string ...$moreParts) : string
    {
        if (str_starts_with($part, $this->config->namespace . '\\')) {
            $part = substr($part, strlen($this->config->namespace) + 1);
        }

        return implode('\\', array_filter([$this->config->namespace, $part, ...$moreParts], fn($part) => $part !== ''));
    }

    public function getNakedType(SymfonyType $type) : SymfonyType
    {
        if ($type instanceof SymfonyType\NullableType) {
            return $type->getWrappedType();
        }

        return $type;
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

        // if ($type instanceof ObjectType) {
        //    return [$type->name];
        // }

        return [];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function mergeArrayShape(SymfonyType $left, SymfonyType $right) : SymfonyType
    {
        if ($right instanceof SymfonyType\NullableType) {
            $right = $right->getWrappedType();
        }

        if ($right instanceof SymfonyType\UnionType) {
            return SymfonyType::union($left, ...$right->getTypes());
        }

        Assert::isInstanceOf($left, ArrayShapeType::class, 'Left type must be an array shape, %s given');
        Assert::isInstanceOf($right, ArrayShapeType::class, 'Right type must be an array shape, %s given');

        $leftShape = $left->getShape();
        $rightShape = $right->getShape();
        $mergedShape = [];

        // Copy all fields from left shape
        foreach ($leftShape as $key => $value) {
            $mergedShape[$key] = $value;
        }

        // Merge fields from right shape
        foreach ($rightShape as $key => $value) {
            if (isset($mergedShape[$key])) {
                // Field exists in both shapes - need to merge
                $leftValue = $mergedShape[$key];

                // Extract the actual types from the array shape format
                $leftType = $leftValue['type'];
                $rightType = $value['type'];

                // If both are array shapes, merge them recursively
                if ($leftType instanceof ArrayShapeType && $rightType instanceof ArrayShapeType) {
                    $mergedType = $this->mergeArrayShape($leftType, $rightType);

                    $mergedShape[$key] = [
                        'type' => $mergedType,
                        'optional' => $leftValue['optional'],
                    ];

                    continue;
                }

                // For non-array shapes, just overwrite
                $mergedShape[$key] = $value;

                continue;
            }

            // Field only exists in right shape
            $mergedShape[$key] = $value;
        }

        return SymfonyType::arrayShape($mergedShape);
    }

    /**
     * @param NodeList<DirectiveNode> $directives
     */
    private function hasIncludeOrSkipDirective(NodeList $directives) : bool
    {
        foreach ($directives as $directive) {
            if (in_array($directive->name->value, ['include', 'skip'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param NodeList<DirectiveNode> $directives
     * @return list<string>
     */
    private function getIndexByDirective(NodeList $directives) : array
    {
        foreach ($directives as $directive) {
            if ($directive->name->value !== 'indexBy') {
                continue;
            }

            if ( ! $directive->arguments[0]->value instanceof StringValueNode) {
                continue;
            }

            return explode('.', $directive->arguments[0]->value->value);
        }

        return [];
    }

    private function singularize(string $fieldName) : string
    {
        $options = $this->inflector->singularize($fieldName);

        return $options[0] ?? $fieldName;
    }
}
