<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Closure;
use Exception;
use Generator;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
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
use JsonSerializable;
use Override;
use ReflectionException;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\CodeGenerator\Group;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\BackedEnumTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\CollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\NullableTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ObjectTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\TypeInitializer;
use Ruudk\GraphQLCodeGenerator\Validator\IndexByValidator;
use Ruudk\GraphQLCodeGenerator\Visitor\IndexByRemover;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Inflector\EnglishInflector;
use function Symfony\Component\String\u;
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
     * @var array<string, SymfonyType>
     */
    private array $fragmentPayloadShapes = [];

    /**
     * @var array<string, Type&NamedType>
     */
    private array $fragmentTypes = [];

    /**
     * @var array<string, SymfonyType|array{SymfonyType, SymfonyType}>
     */
    private array $scalars;
    private DelegatingTypeInitializer $typeInitializer;
    private ?string $schemaPath = null;
    private Optimizer $optimizer;

    /**
     * @var array<class-string<ValidationRule>, ValidationRule>
     */
    public private(set) array $validatorRules;

    /**
     * @param array<string, SymfonyType|array{SymfonyType, SymfonyType}> $scalars
     * @param array<string, SymfonyType> $inputObjectTypes
     * @param array<string, array{SymfonyType, SymfonyType}> $objectTypes
     * @param array<string, SymfonyType> $enumTypes
     * @param list<string> $ignoreTypes
     * @param list<TypeInitializer> $typeInitializers
     *
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(
        Schema | string $schema,
        private readonly string $projectDir,
        private readonly string $queriesDir,
        private readonly string $outputDir,
        private readonly string $namespace,
        private readonly string $client,
        private readonly bool $dumpMethods,
        private readonly bool $dumpOrThrows,
        private readonly bool $dumpDefinition,
        private readonly bool $useNodeNameForEdgeNodes,
        array $scalars = [],
        private array $inputObjectTypes = [],
        private array $objectTypes = [],
        private array $enumTypes = [],
        private readonly array $ignoreTypes = [],
        array $typeInitializers = [],
        private readonly bool $useConnectionNameForConnections = false,
        private readonly bool $useEdgeNameForEdges = false,
        private readonly bool $addNodesOnConnections = false,
        private readonly bool $addSymfonyExcludeAttribute = false,
        private readonly bool $indexByDirective = true,
        private readonly bool $addUnknownCaseToEnums = true,
        private readonly Filesystem $filesystem = new Filesystem(),
        private readonly EnglishInflector $inflector = new EnglishInflector(),
    ) {
        $this->typeInitializer = new DelegatingTypeInitializer(
            new NullableTypeInitializer(),
            new CollectionTypeInitializer(),
            new BackedEnumTypeInitializer($addUnknownCaseToEnums, $namespace),
            new ObjectTypeInitializer(),
            ...$typeInitializers,
        );

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

        if ($this->indexByDirective) {
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

            ...$scalars,
        ];

        $this->optimizer = new Optimizer($this->schema);

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

        if ($this->indexByDirective) {
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
     */
    public function generate() : void
    {
        $this->filesystem->remove($this->outputDir);

        $this->ensureDirectoryExists($this->outputDir);

        $finder = new Finder();
        $finder->files()
            ->in($this->queriesDir)
            ->name('*.graphql')
            ->sortByName();

        if ($this->schemaPath !== null) {
            $finder->notPath(Path::makeRelative($this->schemaPath, $this->queriesDir));
        }

        $operations = [];
        $usedTypesCollector = new UsedTypesCollector($this->schema);

        // First pass: parse all queries to find what types are actually used
        foreach ($finder as $file) {
            $document = Parser::parse($file->getContents());

            $usedTypesCollector->analyze($document);

            $operations[Path::makeRelative($file->getPathname(), $this->projectDir)] = $document;
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

                $this->generateEnumType($typeName, $type);

                continue;
            }

            if ($type instanceof InputObjectType) {
                if ( ! in_array($typeName, $usedTypes, true)) {
                    continue;
                }

                $this->generateInputType($typeName, $type, $type->isOneOf());

                continue;
            }
        }

        $this->generateNodeNotFoundException();

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
                $this->outputDir . '/Fragment/' . $name,
                $fragment->selectionSet,
                $type,
                $fqcn,
                'fragment',
            );

            $this->fragmentPayloadShapes[$name] = $payloadShape;

            $this->generateDataClass(
                $type,
                $fields,
                $payloadShape,
                $this->getPossibleTypes($type),
                $this->outputDir . '/Fragment',
                $fqcn,
                false,
                true,
                $fragment,
                null,
            );
        }

        foreach ($operations as $relativeFilePath => $document) {
            $this->processOperation($document, $relativeFilePath);
        }
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
        $queryDir = $this->outputDir . '/' . $operationType;
        $operationDir = $queryDir . '/' . $operationName;

        $this->ensureDirectoryExists($operationDir);

        $variables = $this->parseVariables($operation);

        $this->generateOperationClass(
            $operationName,
            $queryDir,
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
        $this->generateDataClass(
            $rootType,
            $fields,
            $payloadShape,
            [],
            $operationDir,
            $fqcn,
            true,
            false,
            $operation,
            null,
        );

        $this->generateErrorClass($operationDir, $operationType, $operationName);

        $this->generateExceptionClass($operationDir, $operationType, $operationName, $queryClassName . $operationType . 'FailedException');
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
     * @param array<string, SymfonyType> $variables
     *
     * @throws IOException
     */
    private function generateOperationClass(
        string $operationName,
        string $outputDirectory,
        string $operationType,
        string $queryClassName,
        string $operationDefinition,
        array $variables,
        string $relativeFilePath,
    ) : void {
        $namespace = $this->fullyQualified($operationType);
        $className = $queryClassName . $operationType;
        $failedException = $this->fullyQualified($operationType, $queryClassName, $queryClassName . $operationType . 'FailedException');

        $generator = new CodeGenerator($namespace);
        $class = $generator->dumpFile([
            '// This file was automatically generated and should not be edited.',
            sprintf('// Based on %s', $relativeFilePath),
            '',
            sprintf('final readonly class %s {', $className),
            $generator->indent(function () use ($failedException, $namespace, $variables, $queryClassName, $generator, $operationDefinition, $operationName) {
                yield sprintf('public const string OPERATION_NAME = %s;', var_export($operationName, true));
                yield sprintf('public const string OPERATION_DEFINITION = %s;', $generator->maybeNowDoc($operationDefinition, 'GRAPHQL'));

                yield '';
                yield 'public function __construct(';
                yield $generator->indent([
                    sprintf('private %s $client,', $generator->import($this->client)),
                ]);
                yield ') {}';

                $parameters = $generator->indent(function () use ($generator, $variables) {
                    foreach ($variables as $name => $phpType) {
                        yield sprintf(
                            '%s $%s%s,',
                            $this->dumpPHPType($phpType, $generator->import(...)),
                            $name,
                            $phpType instanceof SymfonyType\NullableType ? ' = null' : '',
                        );
                    }
                });

                yield '';
                yield from $generator->maybeDump(
                    '/**',
                    $this->prefix(' * ', function () use ($generator, $variables) {
                        foreach ($variables as $name => $phpType) {
                            if ( ! $phpType instanceof SymfonyType\CollectionType) {
                                continue;
                            }

                            yield sprintf(
                                '@param %s $%s',
                                $this->dumpPHPDocType($phpType, $generator->import(...)),
                                $name,
                            );
                        }
                    }),
                    ' */',
                );

                if ($variables !== []) {
                    yield 'public function execute(';
                    yield $parameters;
                    yield sprintf(') : %s {', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                } else {
                    yield sprintf('public function execute() : %s', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                    yield '{';
                }

                yield $generator->indent(function () use ($generator, $variables) {
                    yield '$data = $this->client->graphql(';
                    yield $generator->indent(function () use ($generator, $variables) {
                        yield 'self::OPERATION_DEFINITION,';
                        yield '[';
                        yield $generator->indent(function () use ($variables) {
                            foreach ($variables as $name => $phpType) {
                                yield sprintf("'%s' => \$%s,", $name, $name);
                            }
                        });
                        yield '],';
                        yield 'self::OPERATION_NAME,';
                    });
                    yield ');';
                    yield '';
                    yield 'return new Data(';
                    yield $generator->indent([
                        "\$data['data'] ?? [], // @phpstan-ignore argument.type",
                        "\$data['errors'] ?? [] // @phpstan-ignore argument.type",
                    ]);
                    yield ');';
                });
                yield '}';

                if ($this->dumpOrThrows) {
                    yield '';
                    yield from $generator->maybeDump(
                        '/**',
                        $this->prefix(' * ', function () use ($failedException, $generator, $variables) {
                            foreach ($variables as $name => $phpType) {
                                if ( ! $phpType instanceof SymfonyType\CollectionType) {
                                    continue;
                                }

                                yield sprintf(
                                    '@param %s $%s',
                                    $this->dumpPHPDocType($phpType, $generator->import(...)),
                                    $name,
                                );
                            }

                            yield sprintf('@throws %s', $generator->import($failedException));
                        }),
                        ' */',
                    );

                    if ($variables !== []) {
                        yield 'public function executeOrThrow(';
                        yield $parameters;
                        yield sprintf(') : %s {', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                    } else {
                        yield sprintf('public function executeOrThrow() : %s', $generator->import(sprintf($namespace . '\\%s\Data', $queryClassName)));
                        yield '{';
                    }

                    yield $generator->indent(function () use ($failedException, $generator, $variables) {
                        yield '$data = $this->execute(';
                        yield $generator->indent(function () use ($variables) {
                            foreach ($variables as $name => $phpType) {
                                yield sprintf('$%s,', $name);
                            }
                        });
                        yield ');';

                        yield '';
                        yield 'if ($data->errors !== []) {';
                        yield $generator->indent([
                            sprintf('throw new %s($data);', $generator->import($failedException)),
                        ]);
                        yield '}';

                        yield '';
                        yield 'return $data;';
                    });
                    yield '}';
                }
            }),
            '}',
        ]);
        $this->filesystem->dumpFile($outputDirectory . '/' . $className . '.php', $class);
    }

    /**
     * @param list<string> $possibleTypes
     * @throws IOException
     */
    private function generateDataClass(
        NamedType & Type $parentType,
        SymfonyType $fields,
        SymfonyType $payloadShape,
        array $possibleTypes,
        string $outputDirectory,
        string $fqcn,
        bool $isData,
        bool $isFragment,
        null | FragmentDefinitionNode | InlineFragmentNode | OperationDefinitionNode $definitionNode,
        ?SymfonyType $nodesType,
    ) : void {
        if ($fields instanceof SymfonyType\NullableType) {
            $fields = $fields->getWrappedType();
        }

        if ($payloadShape instanceof SymfonyType\NullableType) {
            $payloadShape = $payloadShape->getWrappedType();
        }

        $parts = explode('\\', $fqcn);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);

        $generator = new CodeGenerator($namespace);
        $class = $generator->dumpFile(function () use ($parentType, $nodesType, $fqcn, $definitionNode, $payloadShape, $isData, $fields, $possibleTypes, $generator, $className) {
            yield '// This file was automatically generated and should not be edited.';
            yield '';

            if ($this->dumpDefinition && $definitionNode !== null) {
                yield from $generator->maybeDump(
                    '/**',
                    $this->prefix(' * ', Printer::doPrint($definitionNode)),
                    ' */',
                );
            }

            if ($this->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('final class %s', $generator->import($fqcn));
            yield '{';
            yield $generator->indent(
                function () use ($parentType, $nodesType, $possibleTypes, $className, $fields, $isData, $payloadShape, $generator) {
                    if ($possibleTypes !== []) {
                        yield '/**';
                        yield ' * @var list<string>';
                        yield ' */';
                        yield sprintf(
                            'public const array POSSIBLE_TYPES = [%s];',
                            $generator->join(
                                ', ',
                                array_map(fn(string $type) => var_export($type, true), $possibleTypes),
                            ),
                        );
                    }

                    if ($fields instanceof ArrayShapeType) {
                        foreach ($fields->getShape() as $fieldName => ['type' => $fieldType, 'optional' => $optional]) {
                            Assert::string($fieldName);

                            $nakedFieldType = $this->getNakedType($fieldType);

                            yield '';

                            yield from $generator->maybeDump(
                                '/**',
                                $this->prefix(' * ', function () use ($fieldType, $generator) {
                                    if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                        yield sprintf(
                                            '@var %s',
                                            $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                        );
                                    }
                                }),
                                ' */',
                            );
                            yield sprintf(
                                'public %s $%s {',
                                $this->dumpPHPType($fieldType, $generator->import(...)),
                                $fieldName,
                            );
                            yield $generator->indent(function () use ($parentType, $nakedFieldType, $fieldType, $generator, $fieldName) {
                                if ($nakedFieldType instanceof FragmentObjectType && ($nakedFieldType->fragmentType instanceof InterfaceType || $nakedFieldType->fragmentType instanceof UnionType)) {
                                    yield sprintf(
                                        'get => $this->%s ??= in_array($this->data[\'__typename\'], %s::POSSIBLE_TYPES, true) ? new %s($this->data) : null;',
                                        $fieldName,
                                        $generator->import($nakedFieldType->getClassName()),
                                        $generator->import($nakedFieldType->getClassName()),
                                    );

                                    return;
                                }

                                if ($nakedFieldType instanceof FragmentObjectType && ! $parentType instanceof ObjectType) {
                                    yield sprintf(
                                        'get => $this->%s ??= $this->data[\'__typename\'] === %s ? new %s($this->data) : null;',
                                        $fieldName,
                                        var_export($nakedFieldType->fragmentType->name(), true),
                                        $generator->import($nakedFieldType->getClassName()),
                                    );

                                    return;
                                }

                                if ($nakedFieldType instanceof FragmentObjectType) {
                                    yield sprintf(
                                        'get => $this->%s ??= new %s($this->data);',
                                        $fieldName,
                                        $generator->import($nakedFieldType->getClassName()),
                                    );

                                    return;
                                }

                                yield from $generator->wrap(
                                    sprintf(
                                        'get => $this->%s ??= ',
                                        $fieldName,
                                    ),
                                    $this->typeInitializer->__invoke(
                                        $fieldType,
                                        $generator,
                                        sprintf('$this->data[%s]', var_export($fieldName, true)),
                                    ),
                                    ';',
                                );
                            });
                            yield '}';

                            if ($nakedFieldType instanceof FragmentObjectType && $fieldType instanceof SymfonyType\NullableType) {
                                yield '';
                                yield '/**';
                                yield sprintf(' * @phpstan-assert-if-true !null $this->%s', $fieldName);
                                yield ' */';
                                yield sprintf(
                                    'public bool $is%s {',
                                    $nakedFieldType->fragmentName,
                                );
                                yield $generator->indent(function () use ($nakedFieldType, $generator) {
                                    if ($nakedFieldType->fragmentType instanceof ObjectType) {
                                        yield sprintf(
                                            'get => $this->is%s ??= $this->data[\'__typename\'] === %s;',
                                            $nakedFieldType->fragmentName,
                                            var_export($nakedFieldType->fragmentType->name(), true),
                                        );

                                        return;
                                    }

                                    yield sprintf(
                                        'get => $this->is%s ??= in_array($this->data[\'__typename\'], %s::POSSIBLE_TYPES, true);',
                                        $nakedFieldType->fragmentName,
                                        $generator->import($nakedFieldType->getClassName()),
                                    );
                                });
                                yield '}';
                            }

                            if ($this->dumpOrThrows && $fieldType instanceof SymfonyType\NullableType) {
                                $fieldType = $fieldType->getWrappedType();

                                yield '';
                                yield from $generator->maybeDump(
                                    '/**',
                                    $this->prefix(' * ', function () use ($fieldType, $generator) {
                                        if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                            yield sprintf(
                                                '@var %s',
                                                $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                            );
                                        }

                                        yield '@throws NodeNotFoundException';
                                    }),
                                    ' */',
                                );
                                yield sprintf(
                                    'public %s $%sOrThrow {',
                                    $this->dumpPHPType($fieldType, $generator->import(...)),
                                    $fieldName,
                                );
                                yield $generator->indent(function () use ($className, $generator, $fieldName) {
                                    yield sprintf(
                                        'get => $this->%s ?? throw %s::create(%s, %s);',
                                        $fieldName,
                                        $generator->import($this->fullyQualified('NodeNotFoundException')),
                                        var_export($className, true),
                                        var_export($fieldName, true),
                                    );
                                });
                                yield '}';
                            }
                        }
                    }

                    if ($nodesType !== null) {
                        yield '';
                        yield from $generator->maybeDump(
                            '/**',
                            $this->prefix(' * ', sprintf(
                                '@var %s',
                                $this->dumpPHPDocType($nodesType, $generator->import(...)),
                            )),
                            ' */',
                        );
                        yield sprintf(
                            'public %s $nodes {',
                            $this->dumpPHPType($nodesType, $generator->import(...)),
                        );
                        yield $generator->indent(sprintf(
                            'get => array_map(fn($edge) => $edge->node, $this->edges);',
                        ));
                        yield '}';
                    }

                    if ($isData) {
                        yield '';

                        yield '/**';
                        yield ' * @var list<Error>';
                        yield ' */';
                        yield 'public readonly array $errors;';
                    }

                    yield '';
                    yield from $generator->maybeDump(
                        '/**',
                        $this->prefix(' * ', function () use ($isData, $generator, $payloadShape) {
                            yield sprintf(
                                '@param %s $data',
                                $this->dumpPHPDocType($payloadShape, $generator->import(...)),
                            );

                            if ($isData) {
                                yield sprintf(
                                    '@param %s $errors',
                                    $this->dumpPHPDocType(SymfonyType::list(SymfonyType::arrayShape([
                                        'message' => SymfonyType::string(),
                                        'code' => SymfonyType::string(),
                                        'debugMessage' => [
                                            'type' => SymfonyType::string(),
                                            'optional' => true,
                                        ],
                                    ])), $generator->import(...)),
                                );
                            }
                        }),
                        ' */',
                    );
                    yield 'public function __construct(';
                    yield $generator->indent(function () use ($generator, $payloadShape, $isData) {
                        yield sprintf(
                            'private readonly %s $data,',
                            $this->dumpPHPType($payloadShape, $generator->import(...)),
                        );

                        if ($isData) {
                            yield 'array $errors,';
                        }
                    });

                    if ($isData) {
                        yield ') {';
                        yield $generator->indent(function () {
                            yield '$this->errors = array_map(fn(array $error) => new Error($error), $errors);';
                        });
                        yield '}';
                    } else {
                        yield ') {}';
                    }

                    if ($this->dumpMethods && $fields instanceof ArrayShapeType) {
                        foreach ($fields->getShape() as $fieldName => ['type' => $fieldType]) {
                            Assert::string($fieldName);

                            if ($fieldName === '__typename') {
                                continue;
                            }

                            yield '';

                            if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                yield '/**';
                                yield sprintf(
                                    ' * @return %s',
                                    $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                );
                                yield ' */';
                            }

                            yield sprintf(
                                'public function %s() : %s',
                                $this->getterMethod($fieldName),
                                $this->dumpPHPType($fieldType, $generator->import(...)),
                            );
                            yield '{';
                            yield $generator->indent(function () use ($fieldName) {
                                yield sprintf('return $this->%s;', $fieldName);
                            });
                            yield '}';

                            if ($this->dumpOrThrows && $fieldType instanceof SymfonyType\NullableType) {
                                $fieldType = $fieldType->getWrappedType();

                                yield '';
                                yield from $generator->maybeDump(
                                    '/**',
                                    $this->prefix(' * ', function () use ($fieldType, $generator) {
                                        if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                            yield sprintf(
                                                '@return %s',
                                                $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                            );
                                        }

                                        yield '@throws NodeNotFoundException';
                                    }),
                                    ' */',
                                );
                                yield sprintf(
                                    'public function %sOrThrow() : %s',
                                    $this->getterMethod($fieldName),
                                    $this->dumpPHPType($fieldType, $generator->import(...)),
                                );
                                yield '{';
                                yield $generator->indent(function () use ($fieldName) {
                                    yield sprintf('return $this->%sOrThrow;', $fieldName);
                                });
                                yield '}';
                            }
                        }
                    }

                    if ($isData && $this->dumpMethods) {
                        yield '';
                        yield '/**';
                        yield ' * @return list<Error>';
                        yield ' */';
                        yield 'public function getErrors() : array';
                        yield '{';
                        yield $generator->indent(function () {
                            yield 'return $this->errors;';
                        });
                        yield '}';
                    }

                    if ($nodesType !== null && $this->dumpMethods) {
                        yield '';
                        yield from $generator->maybeDump(
                            '/**',
                            $this->prefix(' * ', sprintf(
                                '@return %s',
                                $this->dumpPHPDocType($nodesType, $generator->import(...)),
                            )),
                            ' */',
                        );
                        yield 'public function getNodes() : array';
                        yield '{';
                        yield $generator->indent(function () {
                            yield 'return $this->nodes;';
                        });
                        yield '}';
                    }
                },
            );
            yield '}';
        });

        $this->filesystem->dumpFile($outputDirectory . '/' . $className . '.php', $class);
    }

    /**
     * @throws IOException
     */
    private function generateErrorClass(string $operationDir, string $operationType, string $operationName) : void
    {
        $generator = new CodeGenerator($this->fullyQualified($operationType, $operationName));
        $class = $generator->dumpFile(function () use ($generator) {
            yield '// This file was automatically generated and should not be edited.';

            yield '';

            if ($this->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield 'final readonly class Error';
            yield '{';
            yield $generator->indent(function () use ($generator) {
                yield 'public string $message;';
                yield 'public string $code;';

                yield '';
                yield from $generator->maybeDump(
                    '/**',
                    $this->prefix(' * ', function () use ($generator) {
                        yield sprintf('@param %s $error', $this->dumpPHPDocType(SymfonyType::arrayShape([
                            'message' => SymfonyType::string(),
                            'code' => SymfonyType::string(),
                            'debugMessage' => [
                                'type' => SymfonyType::string(),
                                'optional' => true,
                            ],
                        ]), $generator->import(...)));
                    }),
                    ' */',
                );
                yield 'public function __construct(array $error)';
                yield '{';
                yield $generator->indent(function () {
                    yield "\$this->message = \$error['debugMessage'] ?? \$error['message'];";
                    yield "\$this->code = \$error['code'];";
                });
                yield '}';
            });
            yield '}';
        });

        $this->filesystem->dumpFile($operationDir . '/Error.php', $class);
    }

    /**
     * @throws IOException
     */
    private function generateExceptionClass(string $outputDir, string $operationType, string $operationName, string $className) : void
    {
        $generator = new CodeGenerator($this->fullyQualified($operationType, $operationName));
        $class = $generator->dumpFile(function () use ($className, $generator) {
            yield '// This file was automatically generated and should not be edited.';

            yield '';
            yield sprintf('final class %s extends %s', $className, $generator->import(Exception::class));
            yield '{';
            yield $generator->indent(function () use ($generator, $className) {
                yield 'public function __construct(';
                yield $generator->indent('public readonly Data $data,');
                yield ') {';
                yield $generator->indent(function () use ($generator, $className) {
                    yield 'parent::__construct(sprintf(';
                    yield $generator->indent([
                        sprintf("'%s failed%%s',", $className),
                        "\$data->errors !== [] ? sprintf(': %s', \$data->errors[0]->message) : '',",
                    ]);
                    yield '));';
                });
                yield '}';
            });
            yield '}';
        });

        $this->filesystem->dumpFile($outputDir . '/' . $className . '.php', $class);
    }

    /**
     * @throws IOException
     */
    private function generateEnumType(string $name, EnumType $type) : void
    {
        if (in_array($name, $this->ignoreTypes, true)) {
            return;
        }

        $generator = new CodeGenerator($this->fullyQualified('Enum'));
        $enumClass = $generator->dumpFile(function () use ($type, $name, $generator) {
            yield '// This file was automatically generated and should not be edited.';
            yield '';
            yield '/**';
            yield ' * @api';
            yield ' */';

            if ($this->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('enum %s: string', $name);
            yield '{';
            yield $generator->indent(function () use ($generator, $type) {
                foreach ($type->getValues() as $value) {
                    Assert::string($value->value, 'Enum value must be a string');

                    if ($value->description !== null) {
                        foreach (explode(PHP_EOL, $value->description) as $description) {
                            yield sprintf('// %s', $description);
                        }
                    }

                    yield sprintf("case %s = '%s';", u($value->value)->lower()->pascal()->toString(), $value->value);

                    if ($value->description !== null) {
                        yield '';
                    }
                }

                if ($this->addUnknownCaseToEnums) {
                    yield '';
                    yield '// When the server returns an unknown enum value, this is the value that will be used.';
                    yield 'case Unknown__ = \'unknown__\';';
                }

                if ($this->dumpMethods) {
                    $numberOfValues = count($type->getValues());
                    foreach ($type->getValues() as $value) {
                        Assert::string($value->value, 'Enum value must be a string');

                        yield '';
                        yield sprintf('public function is%s() : bool', u($value->value)->lower()->pascal()->toString());
                        yield '{';
                        yield $generator->indent(function () use ($numberOfValues, $value) {
                            if ($numberOfValues === 1) {
                                yield '// @phpstan-ignore identical.alwaysTrue';
                            }

                            yield sprintf(
                                'return $this === self::%s;',
                                u($value->value)->lower()->pascal()->toString(),
                            );
                        });
                        yield '}';

                        yield '';
                        yield sprintf(
                            'public static function create%s() : self',
                            u($value->value)->lower()->pascal()->toString(),
                        );
                        yield '{';
                        yield $generator->indent(function () use ($value) {
                            yield sprintf('return self::%s;', u($value->value)->lower()->pascal()->toString());
                        });
                        yield '}';
                    }
                }
            });
            yield '}';
        });

        $this->filesystem->dumpFile($this->outputDir . '/Enum/' . $name . '.php', $enumClass);
    }

    /**
     * @throws IOException
     */
    private function generateInputType(string $name, InputObjectType $type, bool $isOneOf) : void
    {
        if (in_array($name, $this->ignoreTypes, true)) {
            return;
        }

        $generator = new CodeGenerator($this->fullyQualified('Input'));
        $inputClass = $generator->dumpFile(function () use ($isOneOf, $generator, $type) {
            yield '// This file was automatically generated and should not be edited.';

            $description = $type->description();

            if ($description !== null) {
                yield '';
                foreach (explode(PHP_EOL, $description) as $line) {
                    yield sprintf('// %s', $line);
                }
            }

            yield '';

            if ($this->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('final readonly class %s implements %s', $type, $generator->import(JsonSerializable::class));
            yield '{';
            yield $generator->indent(function () use ($isOneOf, $type, $generator) {
                $required = [];
                $optional = [];

                foreach ($type->getFields() as $fieldName => $field) {
                    $fieldType = $this->mapGraphQLTypeToPHPType($field->getType());

                    if ($field->isRequired()) {
                        $required[$fieldName] = $fieldType;

                        continue;
                    }

                    $optional[$fieldName] = $fieldType;
                }

                $fields = [...$required, ...$optional];

                yield from $generator->maybeDump(
                    '/**',
                    function () use ($generator, $fields) {
                        foreach ($fields as $fieldName => $fieldType) {
                            if ( ! $fieldType instanceof SymfonyType\CollectionType) {
                                continue;
                            }

                            yield sprintf(' * @param %s $%s', $this->dumpPHPDocType($fieldType, $generator->import(...)), $fieldName);
                        }
                    },
                    ' */',
                );

                yield sprintf('%s function __construct(', $isOneOf ? 'private' : 'public');
                yield $generator->indent(function () use ($generator, $type) {
                    foreach ($type->getFields() as $fieldName => $field) {
                        $fieldType = $this->mapGraphQLTypeToPHPType($field->getType());

                        yield sprintf(
                            'public %s $%s%s,',
                            $this->dumpPHPType($fieldType, $generator->import(...)),
                            $fieldName,
                            ! $field->isRequired() ? ' = null' : '',
                        );
                    }
                });
                yield ') {}';

                if ($isOneOf) {
                    foreach ($type->getFields() as $fieldName => $field) {
                        $fieldType = $field->getType();

                        if ($fieldType instanceof NullableType) {
                            $fieldType = Type::nonNull($fieldType);
                        }

                        $fieldType = $this->mapGraphQLTypeToPHPType($fieldType);

                        yield '';
                        yield sprintf(
                            'public static function create%s(%s $%s) : self',
                            ucfirst($fieldName),
                            $this->dumpPHPType($fieldType, $generator->import(...)),
                            $fieldName,
                        );
                        yield '{';
                        yield $generator->indent(function () use ($fieldName) {
                            yield sprintf('return new self(%s: $%s);', $fieldName, $fieldName);
                        });
                        yield '}';
                    }
                }

                yield '';
                yield '/**';
                yield from $this->prefix(' * ', sprintf('@return %s', $this->dumpPHPDocType(SymfonyType::arrayShape($fields), $generator->import(...))));
                yield ' */';
                yield $generator->dumpAttribute(Override::class);
                yield 'public function jsonSerialize() : array';
                yield '{';
                yield $generator->indent(function () use ($generator, $fields) {
                    yield 'return [';
                    yield $generator->indent(function () use ($fields) {
                        foreach ($fields as $fieldName => $fieldType) {
                            yield sprintf(
                                "'%s' => \$this->%s,",
                                $fieldName,
                                $fieldName,
                            );
                        }
                    });
                    yield '];';
                });
                yield '}';
            });
            yield '}';
        });

        $this->filesystem->dumpFile($this->outputDir . '/Input/' . $name . '.php', $inputClass);
    }

    /**
     * @throws IOException
     */
    private function generateNodeNotFoundException() : void
    {
        $generator = new CodeGenerator($this->namespace);
        $class = $generator->dumpFile(function () use ($generator) {
            yield '// This file was automatically generated and should not be edited.';

            yield '';
            yield sprintf('final class NodeNotFoundException extends %s', $generator->import(Exception::class));
            yield '{';
            yield $generator->indent(function () use ($generator) {
                yield 'public static function create(string $node, string $property) : self';
                yield '{';
                yield $generator->indent(function () {
                    yield "return new self(sprintf('Field %s.%s is null', \$node, \$property));";
                });
                yield '}';
            });
            yield '}';
        });

        $this->filesystem->dumpFile($this->outputDir . '/NodeNotFoundException.php', $class);
    }

    /**
     * @throws IOException
     */
    private function ensureDirectoryExists(string $dir) : void
    {
        $this->filesystem->mkdir($dir);
    }

    /**
     * @param callable(string): string $importer
     */
    private function dumpPHPType(SymfonyType $type, callable $importer) : string
    {
        if ($type instanceof SymfonyType\NullableType) {
            if ($type->getWrappedType() instanceof SymfonyType\WrappingTypeInterface) {
                return sprintf('null|%s', $this->dumpPHPType($type->getWrappedType(), $importer));
            }

            return sprintf('?%s', $this->dumpPHPType($type->getWrappedType(), $importer));
        }

        if ($type instanceof SymfonyType\CollectionType) {
            return 'array';
        }

        if ($type instanceof SymfonyType\ObjectType) {
            return $importer($type->getClassName());
        }

        if ($type instanceof SymfonyType\UnionType) {
            return implode(
                '|',
                array_unique(
                    array_map(
                        fn(SymfonyType $type) => $this->dumpPHPType($type, $importer),
                        $type->getTypes(),
                    ),
                ),
            );
        }

        return (string) $type;
    }

    /**
     * @param callable(string): string $importer
     */
    private function dumpPHPDocType(SymfonyType $type, callable $importer, int $indentation = 0) : string
    {
        if ($type instanceof SymfonyType\NullableType) {
            return sprintf('null|%s', $this->dumpPHPDocType($type->getWrappedType(), $importer, $indentation));
        }

        if ($type instanceof ArrayShapeType) {
            $items = [];

            foreach ($type->getShape() as $key => ['type' => $itemType, 'optional' => $optional]) {
                $itemKey = sprintf("'%s'", $key);

                if ($optional) {
                    $itemKey = sprintf('%s?', $itemKey);
                }

                $items[] = sprintf('%s: %s', $itemKey, $this->dumpPHPDocType($itemType, $importer, $indentation + 1));
            }

            if ($items === []) {
                return 'array{}';
            }

            $pad = $indentation === 0 ? '' : str_repeat(' ', $indentation * 4);

            return sprintf(
                "array{\n%s    %s,\n%s}",
                $pad,
                implode(sprintf(",\n%s    ", $pad), $items),
                $pad,
            );
        }

        if ($type instanceof SymfonyType\CollectionType) {
            if ($type->isList()) {
                return sprintf('list<%s>', $this->dumpPHPDocType($type->getCollectionValueType(), $importer, $indentation));
            }

            return sprintf(
                'array<%s,%s>',
                $this->dumpPHPDocType($type->getCollectionKeyType(), $importer, $indentation),
                $this->dumpPHPDocType($type->getCollectionValueType(), $importer, $indentation),
            );
        }

        if ($type instanceof SymfonyType\UnionType) {
            return implode(
                '|',
                array_unique(
                    array_map(
                        fn(SymfonyType $type) => $this->dumpPHPDocType($type, $importer, $indentation),
                        $type->getTypes(),
                    ),
                ),
            );
        }

        if ($type instanceof SymfonyType\ObjectType) {
            return $importer($type->getClassName());
        }

        return (string) $type;
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

                    if ($this->indexByDirective) {
                        $indexBy = $this->getIndexByDirective($selection->directives);

                        if ($indexBy !== []) {
                            $indexByType = $this->mapGraphQLTypeToPHPType(RecursiveTypeFinder::find($nakedFieldType, $indexBy));
                        }
                    }

                    $className = ucfirst($this->isList($fieldType) ? $this->singularize($fieldName) : $fieldName);

                    Assert::isInstanceOf($nakedFieldType, NamedType::class, 'Field type must be a named type');

                    if ($this->useNodeNameForEdgeNodes && $fieldName === 'node' && str_ends_with($parent->name(), 'Edge')) {
                        $className = ucfirst($nakedFieldType->name());
                    } elseif ($this->useConnectionNameForConnections && str_ends_with($nakedFieldType->name(), 'Connection')) {
                        $className = ucfirst($nakedFieldType->name());
                    } elseif ($this->useEdgeNameForEdges && str_ends_with($nakedFieldType->name(), 'Edge')) {
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

                    if ($this->addNodesOnConnections && str_ends_with($nakedFieldType->name(), 'Connection')) {
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

                    $this->generateDataClass(
                        $nakedFieldType,
                        $subFields instanceof SymfonyType\CollectionType && $subFields->isList() ? $subFields->getCollectionValueType() : $subFields,
                        $subPayloadShape instanceof SymfonyType\CollectionType && $subPayloadShape->isList() ? $subPayloadShape->getCollectionValueType() : $subPayloadShape,
                        $this->getPossibleTypes($fieldType),
                        $outputDirectory,
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

            if ($selection instanceof FragmentSpreadNode) {
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
                foreach ($nakedFragmentPayloadShape->getShape() as $key => $value) {
                    $payloadShape[$key] = $value;
                }
            }
        }

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

            $subFields = $this->mergeArrayShape(SymfonyType::arrayShape($fields), $subFields);
            $subPayloadShape = $this->mergeArrayShape(SymfonyType::arrayShape($payloadShape), $subPayloadShape);

            $this->generateDataClass(
                $fieldType,
                $subFields instanceof SymfonyType\CollectionType && $subFields->isList() ? $subFields->getCollectionValueType() : $subFields,
                $subPayloadShape instanceof SymfonyType\CollectionType && $subPayloadShape->isList() ? $subPayloadShape->getCollectionValueType() : $subPayloadShape,
                [$fieldType->name()],
                $outputDirectory,
                $this->fullyQualified($fqcn, $className),
                false,
                true,
                $selection,
                null,
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

            $nakedSubPayloadShape = $this->getNakedType($subPayloadShape);
            Assert::isInstanceOf($nakedSubPayloadShape, ArrayShapeType::class, 'Payload shape must be an array shape');
            foreach ($nakedSubPayloadShape->getShape() as $key => $value) {
                $payloadShape[$key] = $value;
            }
        }

        return [
            SymfonyType::arrayShape($fields),
            $fields2,
            SymfonyType::arrayShape($payloadShape),
            SymfonyType::object($fqcn),
        ];
    }

    // TODO MOVE TO Code Generator
    /**
     * Adds a prefix to every line of the iterable
     * @param CodeLines $data
     * @return Generator<string|Group>
     */
    public function prefix(string $prefix, array | Closure | Generator | string $data) : Generator
    {
        foreach (CodeGenerator::resolveIterable($data) as $line) {
            if ($line instanceof Group) {
                yield Group::indent($this->prefix($prefix, $line->lines), $line->indention);

                continue;
            }

            foreach (explode(PHP_EOL, $line) as $singleLine) {
                yield $prefix . $singleLine;
            }
        }
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
        if (str_starts_with($part, $this->namespace . '\\')) {
            $part = substr($part, strlen($this->namespace) + 1);
        }

        return implode('\\', array_filter([$this->namespace, $part, ...$moreParts], fn($part) => $part !== ''));
    }

    private function getterMethod(string $name) : string
    {
        if (preg_match('/^(as|is)[A-Z]/', lcfirst($name)) === 1) {
            return lcfirst($name);
        }

        return sprintf('get%s', ucfirst($name));
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
        foreach ($leftShape as $key => $value) {
            $mergedShape[$key] = $value;
        }

        foreach ($rightShape as $key => $value) {
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
