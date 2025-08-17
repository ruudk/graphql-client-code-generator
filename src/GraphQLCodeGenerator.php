<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Closure;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Exception;
use Generator;
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
use JsonException;
use JsonSerializable;
use Override;
use ReflectionException;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\CodeGenerator\Group;
use Ruudk\GraphQLCodeGenerator\Type\PseudoType;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\BackedEnumTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\CollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\NullableTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ObjectTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\TypeInitializer;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Webmozart\Assert\Assert;

/**
 * @phpstan-import-type CodeLines from CodeGenerator
 */
final class GraphQLCodeGenerator
{
    private readonly Schema $schema;
    private readonly Inflector $inflector;

    /**
     * @var array<string, SymfonyType>
     */
    private array $fragmentPayloadShapes = [];

    /**
     * @var array<string, FragmentDefinitionNode>
     */
    private array $fragments = [];

    /**
     * @var array<string, SymfonyType|array{SymfonyType, SymfonyType}>
     */
    private array $scalars;
    private DelegatingTypeInitializer $typeInitializer;

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
     */
    public function __construct(
        Schema | string $schema,
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
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->typeInitializer = new DelegatingTypeInitializer(
            new NullableTypeInitializer(),
            new CollectionTypeInitializer(),
            new BackedEnumTypeInitializer(),
            new ObjectTypeInitializer(),
            ...$typeInitializers,
        );

        if (is_string($schema) && str_ends_with($schema, '.graphql')) {
            $schema = BuildSchema::build($filesystem->readFile($schema));
        } elseif (is_string($schema) && str_ends_with($schema, '.json')) {
            $introspection = json_decode($filesystem->readFile($schema), true, flags: JSON_THROW_ON_ERROR);
            Assert::isArray($introspection, 'Expected introspection to be an array');
            Assert::keyExists($introspection, 'data', 'Expected introspection to have a "data" key');
            $schema = BuildClientSchema::build($introspection['data']);
        }

        Assert::isInstanceOf($schema, Schema::class, 'Invalid schema given, expected .graphql or .json file or Schema instance');

        $this->schema = $schema;

        $this->inflector = InflectorFactory::create()->build();

        $this->scalars = [
            'ID' => SymfonyType::string(),
            'String' => SymfonyType::string(),
            'Int' => SymfonyType::int(),
            'Float' => SymfonyType::float(),
            'Boolean' => SymfonyType::bool(),

            ...$scalars,
        ];
    }

    public function generate() : void
    {
        $this->filesystem->remove($this->outputDir);

        $this->ensureDirectoryExists($this->outputDir);

        $finder = new Finder();
        $finder->files()->in($this->queriesDir)->name('*.graphql')->sortByName();

        $operations = [];
        $usedTypesCollector = new UsedTypesCollector($this->schema);

        // First pass: parse all queries to find what types are actually used
        foreach ($finder as $file) {
            $document = Parser::parse($file->getContents());

            $usedTypesCollector->analyze($document);

            $operations[$file->getPathname()] = $document;
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

                $this->generateInputType($typeName, $type);

                continue;
            }
        }

        $this->generateNodeNotFoundException();

        $ordered = FragmentOrderer::orderFragments($operations);
        foreach (array_reverse($ordered) as $fragment) {
            TypeNameVisitor::visit($fragment);

            $name = $fragment->name->value;

            $this->fragments[$name] = $fragment;

            $type = $this->schema->getType($fragment->typeCondition->name->value);

            Assert::notNull($type, 'Expected fragment type to be defined');

            $type = Type::nonNull($type);

            Assert::notNull($type, 'Expected type to be defined');

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

        foreach ($operations as $document) {
            $this->processOperation($document);
        }
    }

    private function processOperation(DocumentNode $document) : void
    {
        TypeNameVisitor::visit($document);

        // TODO Why not handle multiple operations?
        $operation = $this->getFirstOperation($document);

        if ($operation === null) {
            return;
        }

        Assert::notNull($operation->name, 'Expected operation to have a name');

        $operationName = $operation->name->value;
        Assert::notNull($operationName);

        $operationType = ucfirst($operation->operation);

        $operationDefinition = Printer::doPrint($document);

        $queryClassName = $operationName;
        $queryDir = $this->outputDir . '/' . $operationType;
        $operationDir = $queryDir . '/' . $operationName;

        $this->ensureDirectoryExists($operationDir);

        $variables = $this->parseVariables($operation);

        $this->generateOperationClass($operationName, $queryDir, $operationType, $queryClassName, $operationDefinition, $variables);

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
    private function generateOperationClass(string $operationName, string $outputDirectory, string $operationType, string $queryClassName, string $operationDefinition, array $variables) : void
    {
        $namespace = $this->fullyQualified($operationType);
        $className = $queryClassName . $operationType;
        $failedException = $this->fullyQualified($operationType, $queryClassName, $queryClassName . $operationType . 'FailedException');

        $generator = new CodeGenerator($namespace);
        $class = $generator->dump([
            '// This file was automatically generated and should not be edited.',
            '',
            sprintf('final readonly class %s {', $className),
            $generator->indent(function () use ($failedException, $namespace, $variables, $queryClassName, $generator, $operationDefinition, $operationName) {
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

                yield $generator->indent(function () use ($operationDefinition, $generator, $operationName, $variables) {
                    yield '$data = $this->client->graphql(';
                    yield $generator->indent(function () use ($generator, $operationDefinition, $operationName, $variables) {
                        yield sprintf('%s,', $generator->maybeNowDoc($operationDefinition, 'GRAPHQL'));
                        yield '[';
                        yield $generator->indent(function () use ($variables) {
                            foreach ($variables as $name => $phpType) {
                                yield sprintf("'%s' => \$%s,", $name, $name);
                            }
                        });
                        yield '],';
                        yield sprintf('%s,', var_export($operationName, true));
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
     */
    private function generateDataClass(
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
        $class = $generator->dump(function () use ($nodesType, $fqcn, $definitionNode, $payloadShape, $isData, $fields, $possibleTypes, $generator, $className, $isFragment) {
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
                function () use ($nodesType, $isFragment, $possibleTypes, $className, $fields, $isData, $payloadShape, $generator) {
                    if ($isFragment) {
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

                            $nullableFieldType = $this->getNonNullableType($fieldType);

                            yield '';

                            yield from $generator->maybeDump(
                                '/**',
                                $this->prefix(' * ', function () use ($fieldType, $generator) {
                                    if ($this->getNonNullableType($fieldType) instanceof SymfonyType\CollectionType) {
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
                            yield $generator->indent(function () use ($nullableFieldType, $fieldType, $generator, $fieldName) {
                                if ($nullableFieldType instanceof FragmentObjectType) {
                                    yield sprintf(
                                        'get => $this->%s ??= in_array($this->data[\'__typename\'], %s::POSSIBLE_TYPES, true) ? new %s($this->data) : null;',
                                        $fieldName,
                                        $generator->import($nullableFieldType->getClassName()),
                                        $generator->import($nullableFieldType->getClassName()),
                                    );

                                    return;
                                }

                                yield sprintf(
                                    'get => $this->%s ??= %s;',
                                    $fieldName,
                                    $this->typeInitializer->__invoke(
                                        $fieldType,
                                        $generator->import(...),
                                        sprintf('$this->data[%s]', var_export($fieldName, true)),
                                    ),
                                );
                            });
                            yield '}';

                            if ($nullableFieldType instanceof FragmentObjectType) {
                                yield '';
                                yield '/**';
                                yield sprintf(' * @phpstan-assert-if-true !null $this->%s', $fieldName);
                                yield ' */';
                                yield sprintf(
                                    'public bool $is%s {',
                                    $nullableFieldType->fragmentName,
                                );
                                yield $generator->indent(function () use ($nullableFieldType, $generator) {
                                    yield sprintf(
                                        'get => $this->is%s ??= in_array($this->data[\'__typename\'], %s::POSSIBLE_TYPES, true);',
                                        $nullableFieldType->fragmentName,
                                        $generator->import($nullableFieldType->getClassName()),
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
                                        if ($this->getNonNullableType($fieldType) instanceof SymfonyType\CollectionType) {
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

                            if ($this->getNonNullableType($fieldType) instanceof SymfonyType\CollectionType) {
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
                                        if ($this->getNonNullableType($fieldType) instanceof SymfonyType\CollectionType) {
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

    private function generateErrorClass(string $operationDir, string $operationType, string $operationName) : void
    {
        $generator = new CodeGenerator($this->fullyQualified($operationType, $operationName));
        $class = $generator->dump(function () use ($generator) {
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

    private function generateExceptionClass(string $outputDir, string $operationType, string $operationName, string $className) : void
    {
        $generator = new CodeGenerator($this->fullyQualified($operationType, $operationName));
        $class = $generator->dump(function () use ($className, $generator) {
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

    private function generateEnumType(string $name, EnumType $type) : void
    {
        if (in_array($name, $this->ignoreTypes, true)) {
            return;
        }

        $generator = new CodeGenerator($this->fullyQualified('Enum'));
        $enumClass = $generator->dump(function () use ($type, $name, $generator) {
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
                        // TODO 2025-08-01 extract to separate method
                        foreach (explode(PHP_EOL, $value->description) as $description) {
                            yield sprintf('// %s', $description);
                        }
                    }

                    yield sprintf("case %s = '%s';", u($value->value)->lower()->pascal()->toString(), $value->value);

                    if ($value->description !== null) {
                        yield '';
                    }
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

    private function generateInputType(string $name, InputObjectType $type) : void
    {
        if (in_array($name, $this->ignoreTypes, true)) {
            return;
        }

        $generator = new CodeGenerator($this->fullyQualified('Input'));
        $inputClass = $generator->dump(function () use ($generator, $type) {
            yield '// This file was automatically generated and should not be edited.';

            if ($type->description() !== null) {
                yield '';
                // TODO 2025-08-01 extract to separate method
                foreach (explode(PHP_EOL, $type->description()) as $line) {
                    yield sprintf('// %s', $line);
                }
            }

            yield '';

            if ($this->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('final readonly class %s implements %s', $type, $generator->import(JsonSerializable::class));
            yield '{';
            yield $generator->indent(function () use ($type, $generator) {
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

                yield 'public function __construct(';
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

    private function generateNodeNotFoundException() : void
    {
        $generator = new CodeGenerator($this->namespace);
        $class = $generator->dump(function () use ($generator) {
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

    private function getFirstOperation(DocumentNode $document) : ?OperationDefinitionNode
    {
        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                return $definition;
            }
        }

        return null;
    }

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

            foreach ($type->getShape() as $key => ['type' => $type, 'optional' => $optional]) {
                $itemKey = sprintf("'%s'", $key);

                if ($optional) {
                    $itemKey = sprintf('%s?', $itemKey);
                }

                $items[] = sprintf('%s: %s', $itemKey, $this->dumpPHPDocType($type, $importer, $indentation + 1));
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
     * @return array{SymfonyType, array<string, SymfonyType>, SymfonyType, SymfonyType}
     */
    private function parseSelectionSet(
        string $outputDirectory,
        SelectionSetNode $selectionSet,
        Type $parent,
        string $fqcn,
        string $path,
        ?bool $nullable = null,
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
                SymfonyType::list($type),
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
                    $className = ucfirst($this->isList($fieldType) ? $this->inflector->singularize($fieldName) : $fieldName);

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
                    );

                    $this->generateDataClass(
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
                        $this->addNodesOnConnections && str_ends_with($nakedFieldType->name(), 'Connection') ? SymfonyType::list($subFields2[$path . '.' . $fieldName . '.edges.*.node']) : null,
                    );

                    if ($this->hasIncludeDirective($selection->directives)) {
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

        $shapes = [SymfonyType::arrayShape($payloadShape)];
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FragmentSpreadNode) {
                $fieldName = lcfirst($selection->name->value);
                $fields[$fieldName] = SymfonyType::nullable(new FragmentObjectType($this->fullyQualified('Fragment', $selection->name->value), $selection->name->value));
                $fields2[$path . '.' . $fieldName] = SymfonyType::nullable(new FragmentObjectType($this->fullyQualified('Fragment', $selection->name->value), $selection->name->value));

                dump($selection->name->value, (string) $this->fragmentPayloadShapes[$selection->name->value]);
                $nakedFragmentPayloadShape = $this->getNonNullableType($this->fragmentPayloadShapes[$selection->name->value]);
                Assert::isInstanceOf(
                    $nakedFragmentPayloadShape,
                    ArrayShapeType::class,
                    'Payload shape must be an array shape, %s given',
                );

                $fragment = $this->fragments[$selection->name->value];

                $type = $this->schema->getType($fragment->typeCondition->name->value);

                Assert::notNull($type, 'Expected type to be defined');

                $possibleTypes = $this->getPossibleTypes($type);

                foreach ($possibleTypes as $possibleType) {
                    $shape = $nakedFragmentPayloadShape->getShape();
                    $shape['__typename'] = [
                        'type' => new PseudoType(var_export($possibleType, true)),
                        'optional' => false,
                    ];
                    $shapes[] = SymfonyType::arrayShape($shape);
                }

                // Assert::isInstanceOf($this->getNonNullableType($this->fragmentPayloadShapes[$selection->name->value]), ArrayShapeType::class, 'Fragment shape must be an array shape');
                // foreach ($this->getNonNullableType($this->fragmentPayloadShapes[$selection->name->value])->getShape() as $key => ['type' => $type]) {
                //    if (isset($payloadShape[$key]) && $this->getNonNullableType($payloadShape[$key]) instanceof ArrayShapeType) {
                //        $merged = $this->mergeArrayShape(
                //            $this->getNonNullableType($payloadShape[$key]),
                //            $this->getNonNullableType($type),
                //        );
                //
                //        if ($payloadShape[$key] instanceof NullableType) {
                //            $merged = SymfonyType::nullable($merged);
                //        }
                //
                //        $payloadShape[$key] = $merged;
                //
                //        continue;
                //    }
                //
                //    $payloadShape[$key] = $type;
                // }
            }

            if ($selection instanceof InlineFragmentNode) {
                // TODO
                Assert::notNull($selection->typeCondition, 'Inline fragment must have a type condition for now');

                $fieldType = $this->schema->getType($selection->typeCondition->name->value);

                Assert::isInstanceOf($fieldType, NamedType::class, 'Type condition must be a named type');

                $className = sprintf('As%s', $fieldType->name());
                $fieldName = sprintf('as%s', $fieldType->name());

                [$subFields, $subFields2, $subPayloadShape, $subType] = $this->parseSelectionSet(
                    $outputDirectory . '/' . $className,
                    $selection->selectionSet,
                    $fieldType,
                    $fqcn . '\\' . $className,
                    $path . '.' . $fieldName,
                );

                $mergedSubFields = $this->mergeArrayShape(SymfonyType::arrayShape($fields), $subFields);
                $mergedSubPayloadShape = $this->mergeArrayShape(
                    SymfonyType::arrayShape($payloadShape),
                    $subPayloadShape,
                );

                $possibleTypes = $this->getPossibleTypes($fieldType);
                $this->generateDataClass(
                    $mergedSubFields instanceof SymfonyType\CollectionType && $mergedSubFields->isList(
                    ) ? $mergedSubFields->getCollectionValueType() : $mergedSubFields,
                    $mergedSubPayloadShape instanceof SymfonyType\CollectionType && $mergedSubPayloadShape->isList(
                    ) ? $mergedSubPayloadShape->getCollectionValueType() : $mergedSubPayloadShape,
                    $possibleTypes,
                    $outputDirectory,
                    $this->fullyQualified($fqcn, $className),
                    false,
                    true,
                    $selection,
                    null,
                );

                $fields[$fieldName] = SymfonyType::nullable(
                    new FragmentObjectType($this->fullyQualified($fqcn, $className), $fieldType->name()),
                );
                $fields2[$path . '.' . $fieldName] = SymfonyType::nullable(
                    new FragmentObjectType($this->fullyQualified($fqcn, $className), $fieldType->name()),
                );
                $fields2 = [...$fields2, ...$subFields2];

                // This is not good. It should fetch the possible types for the fragment, and then duplicate
                // the shape, with __typename hardcoded, and then add it to the shape.
                // https://phpstan.org/r/333381ea-6d54-4f5c-8bef-30dd17d581a7

                // $nakedSubPayloadShape = $this->getNonNullableType($subPayloadShape);
                // Assert::isInstanceOf(
                //    $nakedSubPayloadShape,
                //    ArrayShapeType::class,
                //    'Payload shape must be an array shape',
                // );
                //
                // foreach ($possibleTypes as $possibleType) {
                //    $shape = $nakedSubPayloadShape->getShape();
                //    $shape['__typename'] = [
                //        'type' => new PseudoType(var_export($possibleType, true)),
                //        'optional' => false,
                //    ];
                //    $shapes[] = SymfonyType::arrayShape($shape);
                // }

                continue;
            }
        }

        if (count($shapes) > 1) {
            $payloadShape = SymfonyType::union(...$shapes);
        } else {
            $payloadShape = $shapes[0];
        }

        return [
            SymfonyType::arrayShape($fields),
            $fields2,
            $payloadShape,
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

    public function getNonNullableType(SymfonyType $type) : SymfonyType
    {
        if ($type instanceof SymfonyType\NullableType) {
            return $type->getWrappedType();
        }

        return $type;
    }

    /**
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

        if ($type instanceof ObjectType) {
            return [$type->name];
        }

        return [];
    }

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
    private function hasIncludeDirective(NodeList $directives) : bool
    {
        foreach ($directives as $directive) {
            if ($directive->name->value === 'include') {
                return true;
            }
        }

        return false;
    }
}
