<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Exception;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\ListTypeNode;
use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\NonNullTypeNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\AST\VariableNode;
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
use Ruudk\GraphQLCodeGenerator\TypeInitializer\BackedEnumTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\CollectionTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\NullableTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ObjectTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\TypeInitializer;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Webmozart\Assert\Assert;

final class CodeGenerator
{
    private readonly Schema $schema;
    private readonly Inflector $inflector;

    /**
     * @var array<string, SymfonyType>
     */
    private array $fragmentPayloadShapes = [];

    /**
     * @var array<string, SymfonyType|array{SymfonyType, SymfonyType}>
     */
    private array $scalars;
    private DelegatingTypeInitializer $typeInitializer;

    /**
     * @param array<string, SymfonyType|array{SymfonyType, SymfonyType}> $scalars
     * @param array<string, SymfonyType> $types
     * @param list<string> $ignoreTypes
     * @param list<TypeInitializer> $typeInitializers
     *
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     * @throws JsonException
     * @throws ReflectionException
     */
    public function __construct(
        string $schemaPath,
        private readonly string $queriesDir,
        private readonly string $outputDir,
        private readonly string $namespace,
        private readonly string $client,
        private readonly bool $dumpMethods,
        private readonly bool $dumpOrThrows,
        private readonly bool $dumpDefinition,
        array $scalars = [],
        private array $types = [],
        private readonly array $ignoreTypes = [],
        array $typeInitializers = [],
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        $this->typeInitializer = new DelegatingTypeInitializer(
            new NullableTypeInitializer(),
            new CollectionTypeInitializer(),
            new BackedEnumTypeInitializer(),
            new ObjectTypeInitializer(),
            ...$typeInitializers,
        );

        $schema = $filesystem->readFile($schemaPath);

        if (str_ends_with($schemaPath, '.graphql')) {
            $this->schema = BuildSchema::build($schema);
        } elseif (str_ends_with($schemaPath, '.json')) {
            $schema = json_decode($schema, true, flags: JSON_THROW_ON_ERROR);
            $this->schema = BuildClientSchema::build($schema['data']);
        }

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
    private array $usedTypes = [];

    public function generate() : void
    {
        $this->filesystem->remove($this->outputDir);

        $this->ensureDirectoryExists($this->outputDir);
        $this->ensureDirectoryExists($this->outputDir . '/Query');
        $this->ensureDirectoryExists($this->outputDir . '/Mutation');

        $finder = new Finder();
        $finder->files()->in($this->queriesDir)->name('*.graphql')->sortByName();

        // Reset tracking arrays
        $this->usedTypes = [];

        $operations = [];

        // First pass: parse all queries to find what types are actually used
        foreach ($finder as $file) {
            $document = Parser::parse($file->getContents());
            $this->collectUsedTypes($document);

            $operations[$file->getPathname()] = $document;
        }

        foreach ($this->schema->getTypeMap() as $typeName => $type) {
            if (str_starts_with($typeName, '__')) {
                continue;
            }

            if ( ! isset($this->usedTypes[$typeName])) {
                continue;
            }

            if ($type instanceof EnumType) {
                $this->types[$typeName] ??= new BackedEnumType($this->fullyQualified('Enum', $typeName), SymfonyType::string());

                continue;
            }

            if ($type instanceof InputObjectType) {
                $this->types[$typeName] ??= SymfonyType::object($this->fullyQualified('Input', $typeName));

                continue;
            }
        }

        foreach ($this->schema->getTypeMap() as $typeName => $type) {
            if (str_starts_with($typeName, '__')) {
                continue;
            }

            if ($type instanceof EnumType) {
                if ( ! isset($this->usedTypes[$typeName])) {
                    continue;
                }

                $this->generateEnumType($typeName, $type);

                continue;
            }

            if ($type instanceof InputObjectType) {
                if ( ! isset($this->usedTypes[$typeName])) {
                    continue;
                }

                $this->generateInputType($typeName, $type);

                continue;
            }
        }

        $this->generateNodeNotFoundException();
        // $this->generateWrongTypeForFragmentException();

        $ordered = FragmentOrderer::orderFragments($operations);
        foreach (array_reverse($ordered) as $fragment) {
            $name = $fragment->name->value;

            $type = $this->schema->getType($fragment->typeCondition->name->value);

            $fqcn = $this->fullyQualified('Fragment', $name);
            [$fields, $payloadShape, , $possibleTypes] = $this->parseSelectionSet(
                $this->outputDir . '/Fragment/' . $name,
                $fragment->selectionSet,
                $type,
                $fqcn,
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
            );
        }

        foreach ($operations as $document) {
            $this->processOperation($document);
        }
    }

    private function processOperation(DocumentNode $document) : void
    {
        // TODO Why not handle multiple operations?
        $operation = $this->getFirstOperation($document);

        if ($operation === null) {
            return;
        }

        $operationName = $operation->name->value;
        Assert::notNull($operationName);

        $operationType = ucfirst($operation->operation);

        $this->addTypenameToSelectionSetsRecursive($operation->selectionSet);

        $operationDefinition = Printer::doPrint($document);

        $queryClassName = $operationName;
        $queryDir = $this->outputDir . '/' . $operationType;
        $operationDir = $queryDir . '/' . $operationName;

        $this->ensureDirectoryExists($operationDir);

        $variables = $this->parseVariables($operation);

        $this->generateOperationClass($operationName, $queryDir, $operationType, $queryClassName, $operationDefinition, $variables);

        $rootType = $operationType === 'Query' ? $this->schema->getQueryType() : $this->schema->getMutationType();

        $fqcn = $this->fullyQualified($operationType, $operationName, 'Data');
        [$fields, $payloadShape, , $possibleTypes] = $this->parseSelectionSet(
            $operationDir . '/Data',
            $operation->selectionSet,
            $rootType,
            $fqcn,
        );
        $this->generateDataClass(
            $fields,
            $payloadShape,
            $possibleTypes,
            $operationDir,
            $fqcn,
            true,
            false,
            $operation,
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

            if (isset($this->types[$type->name->value])) {
                return $this->types[$type->name->value];
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

        if ($type instanceof NamedType) {
            if ( ! $builtInOnly && isset($this->types[$type->name()])) {
                return $this->types[$type->name()];
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

        $generator = new \Ruudk\CodeGenerator\CodeGenerator($namespace);
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
                    yield "return new Data(\$data['data'] ?? [], \$data['errors'] ?? []);";
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

    private function generateDataClass(
        SymfonyType $fields,
        SymfonyType $payloadShape,
        array $possibleTypes,
        string $outputDirectory,
        string $fqcn,
        bool $isData,
        bool $isFragment,
        null | FragmentDefinitionNode | InlineFragmentNode | OperationDefinitionNode $definitionNode,
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

        $generator = new \Ruudk\CodeGenerator\CodeGenerator($namespace);
        $class = $generator->dump(function () use ($fqcn, $definitionNode, $payloadShape, $isData, $fields, $possibleTypes, $generator, $className, $isFragment) {
            yield '// This file was automatically generated and should not be edited.';
            yield '';

            if ($this->dumpDefinition && $definitionNode !== null) {
                yield from $generator->maybeDump(
                    '/**',
                    $this->prefix(' * ', Printer::doPrint($definitionNode)),
                    ' */',
                );
            }

            yield $generator->dumpAttribute(Exclude::class);
            yield sprintf('final class %s', $generator->import($fqcn));
            yield '{';
            yield $generator->indent(
                function () use ($isFragment, $possibleTypes, $className, $fields, $isData, $payloadShape, $generator) {
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

                    if ($fields instanceof SymfonyType\ArrayShapeType) {
                        foreach ($fields->getShape() as $fieldName => ['type' => $fieldType, 'optional' => $optional]) {
                            yield '';

                            yield from $generator->maybeDump(
                                '/**',
                                $this->prefix(' * ', function () use ($fieldType, $generator) {
                                    if ($fieldType instanceof SymfonyType\CollectionType) {
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
                            yield $generator->indent(function () use ($fieldType, $generator, $fieldName) {
                                if ($this->getNonNullableType($fieldType) instanceof FragmentObjectType) {
                                    yield sprintf(
                                        'get => in_array($this->__typename, %s::POSSIBLE_TYPES, true) ? new %s($this->data) : null;',
                                        $generator->import($this->getNonNullableType($fieldType)->getClassName()),
                                        $generator->import($this->getNonNullableType($fieldType)->getClassName()),
                                    );

                                    return;
                                }

                                yield sprintf(
                                    'get => %s;',
                                    $this->typeInitializer->__invoke(
                                        $fieldType,
                                        $generator->import(...),
                                        sprintf('$this->data[%s]', var_export($fieldName, true)),
                                    ),
                                );
                            });
                            yield '}';

                            if ($this->getNonNullableType($fieldType) instanceof FragmentObjectType) {
                                yield '';
                                yield '/**';
                                yield sprintf(' * @phpstan-assert-if-true !null $this->%s', $fieldName);
                                yield ' */';
                                yield sprintf(
                                    'public bool $is%s {',
                                    $this->getNonNullableType($fieldType)->fragmentName,
                                );
                                yield $generator->indent(function () use ($fieldType, $generator) {
                                    yield sprintf(
                                        'get => in_array($this->__typename, %s::POSSIBLE_TYPES, true);',
                                        $generator->import($this->getNonNullableType($fieldType)->getClassName()),
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
                                        if ($fieldType instanceof SymfonyType\CollectionType) {
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

                    if ($this->dumpMethods && $fields instanceof SymfonyType\ArrayShapeType) {
                        foreach ($fields->getShape() as $fieldName => ['type' => $fieldType]) {
                            if ($fieldName === '__typename') {
                                continue;
                            }

                            yield '';

                            if ($fieldType instanceof SymfonyType\CollectionType) {
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
                                        if ($fieldType instanceof SymfonyType\CollectionType) {
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
                },
            );
            yield '}';
        });

        $this->filesystem->dumpFile($outputDirectory . '/' . $className . '.php', $class);
    }

    private function generateErrorClass(string $operationDir, string $operationType, string $operationName) : void
    {
        $generator = new \Ruudk\CodeGenerator\CodeGenerator($this->fullyQualified($operationType, $operationName));
        $class = $generator->dump(function () use ($generator) {
            yield '// This file was automatically generated and should not be edited.';

            yield '';
            yield $generator->dumpAttribute(Exclude::class);
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
        $generator = new \Ruudk\CodeGenerator\CodeGenerator($this->fullyQualified($operationType, $operationName));
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

        $generator = new \Ruudk\CodeGenerator\CodeGenerator($this->fullyQualified('Enum'));
        $enumClass = $generator->dump([
            '// This file was automatically generated and should not be edited.',
            '',
            '/**',
            ' * @api',
            ' */',
            $generator->dumpAttribute(Exclude::class),
            sprintf('enum %s: string', $name),
            '{',
            $generator->indent(function () use ($generator, $type) {
                foreach ($type->getValues() as $value) {
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

                foreach ($type->getValues() as $value) {
                    yield '';
                    yield sprintf('public function is%s() : bool', u($value->value)->lower()->pascal()->toString());
                    yield '{';
                    yield $generator->indent(function () use ($value) {
                        yield sprintf('return $this === self::%s;', u($value->value)->lower()->pascal()->toString());
                    });
                    yield '}';

                    yield '';
                    yield sprintf('public function create%s() : self', u($value->value)->lower()->pascal()->toString());
                    yield '{';
                    yield $generator->indent(function () use ($value) {
                        yield sprintf('return self::%s;', u($value->value)->lower()->pascal()->toString());
                    });
                    yield '}';
                }
            }),
            '}',
        ]);

        $this->filesystem->dumpFile($this->outputDir . '/Enum/' . $name . '.php', $enumClass);
    }

    private function generateInputType(string $name, InputObjectType $type) : void
    {
        if (in_array($name, $this->ignoreTypes, true)) {
            return;
        }

        $generator = new \Ruudk\CodeGenerator\CodeGenerator($this->fullyQualified('Input'));
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
            yield $generator->dumpAttribute(Exclude::class);
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
                yield $this->prefix(' * ', sprintf('@return %s', $this->dumpPHPDocType(SymfonyType::arrayShape($fields), $generator->import(...))));
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
        $generator = new \Ruudk\CodeGenerator\CodeGenerator($this->namespace);
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

    private function addTypenameToSelectionSetsRecursive(?SelectionSetNode $selectionSet, bool $addToCurrentLevel = false) : void
    {
        if ($selectionSet === null) {
            return;
        }

        // Add __typename to current level if requested (for fragments)
        if ($addToCurrentLevel) {
            $this->addTypenameToSelectionSet($selectionSet);
        }

        // Recursively add to nested selection sets
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode && $selection->selectionSet) {
                $this->addTypenameToSelectionSet($selection->selectionSet);
                $this->addTypenameToSelectionSetsRecursive($selection->selectionSet);
            } elseif ($selection instanceof InlineFragmentNode && $selection->selectionSet) {
                // Don't add __typename to inline fragments themselves, only to their nested fields
                $this->addTypenameToSelectionSetsRecursive($selection->selectionSet);
            }
        }
    }

    private function addTypenameToSelectionSet(SelectionSetNode $selectionSet) : void
    {
        // Check if __typename already exists
        $hasTypename = false;
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode && $selection->name->value === '__typename') {
                $hasTypename = true;

                break;
            }
        }

        // Add __typename if it doesn't exist
        if ( ! $hasTypename) {
            $typenameField = new FieldNode([
                'name' => new NameNode([
                    'value' => '__typename',
                ]),
                'alias' => null,
                'arguments' => new NodeList([]),
                'directives' => new NodeList([]),
                'selectionSet' => null,
            ]);

            // Add __typename as the first selection
            $selections = array_merge([$typenameField], iterator_to_array($selectionSet->selections));
            $selectionSet->selections = new NodeList($selections);
        }
    }

    private function ensureDirectoryExists(string $dir) : void
    {
        $this->filesystem->mkdir($dir);
    }

    private function collectUsedTypes(DocumentNode $document) : void
    {
        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                $parent = match ($definition->operation) {
                    'query' => $this->schema->getQueryType(),
                    'mutation' => $this->schema->getMutationType(),
                };
                $this->collectUsedTypesFromSelectionSet($definition->selectionSet, $parent);

                // Collect types from variables
                if ($definition->variableDefinitions) {
                    foreach ($definition->variableDefinitions as $varDef) {
                        $this->collectUsedTypesFromTypeNode($varDef->type);
                    }
                }

                continue;
            }

            if ($definition instanceof FragmentDefinitionNode) {
                $this->collectUsedTypesFromSelectionSet($definition->selectionSet, $this->schema->getType($definition->typeCondition->name->value));
            }
        }
    }

    private function collectUsedTypesFromTypeNode(TypeNode $typeNode) : void
    {
        if ($typeNode instanceof ListTypeNode || $typeNode instanceof NonNullTypeNode) {
            $this->collectUsedTypesFromTypeNode($typeNode->type);

            return;
        }

        if ($typeNode instanceof NamedTypeNode) {
            $typeName = $typeNode->name->value;
            $this->usedTypes[$typeName] = true;

            // If it's an input type, collect its field types recursively
            $type = $this->schema->getType($typeName);
        }

        if ($type instanceof HasFieldsType) {
            foreach ($type->getFields() as $field) {
                $this->collectUsedTypesFromGraphQLType($field->getType());
            }
        }
    }

    private function collectUsedTypesFromGraphQLType(Type $type) : void
    {
        if ($type instanceof WrappingType) {
            $type = $type->getInnermostType();
        }

        if ($type instanceof NamedType) {
            $this->usedTypes[$type->name] = true;

            // If it's an input type, collect its field types recursively
            if ($type instanceof HasFieldsType) {
                foreach ($type->getFields() as $field) {
                    $this->collectUsedTypesFromGraphQLType($field->getType());
                }
            }
        }
    }

    private function collectUsedTypesFromSelectionSet(SelectionSetNode $selectionSet, Type $parent) : void
    {
        if ($parent instanceof WrappingType) {
            $parent = $parent->getInnermostType();
        }

        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                // Check field arguments for input types
                if ($selection->arguments) {
                    foreach ($selection->arguments as $argument) {
                        $this->collectUsedTypesFromValue($argument->value);
                    }
                }

                if ($selection->name->value === '__typename') {
                    continue;
                }

                // Recurse into nested selections
                if ($selection->selectionSet) {
                    $fieldType = $parent->getField($selection->name->value)->getType();
                    $this->collectUsedTypesFromSelectionSet($selection->selectionSet, $fieldType);
                }

                continue;
            }

            if ($selection instanceof InlineFragmentNode) {
                if ($selection->typeCondition) {
                    $typeName = $selection->typeCondition->name->value;
                    $this->usedTypes[$typeName] = true;
                }

                $this->collectUsedTypesFromSelectionSet($selection->selectionSet, $this->schema->getType($typeName));

                continue;
            }

            if ($selection instanceof FragmentSpreadNode) {
                // Fragment spreads are handled when we process fragment definitions
            }
        }
    }

    private function collectUsedTypesFromValue($value) : void
    {
        if ($value instanceof VariableNode) {
            // Variable types are collected from variable definitions
            return;
        }

        if ($value instanceof ListValueNode) {
            foreach ($value->values as $item) {
                $this->collectUsedTypesFromValue($item);
            }
        } elseif ($value instanceof ObjectValueNode) {
            foreach ($value->fields as $field) {
                $this->collectUsedTypesFromValue($field->value);
            }
        }
    }

    private function getFieldTypeFromSchema(string $typeName, string $fieldName) : ?Type
    {
        $type = $this->schema->getType($typeName);

        if ( ! $type instanceof ObjectType) {
            return null;
        }

        try {
            $field = $type->getField($fieldName);

            return $field->getType();
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @return list<FragmentDefinitionNode>
     */
    private function getFragments(DocumentNode $document) : array
    {
        $fragments = [];

        foreach ($document->definitions as $definition) {
            if ( ! $definition instanceof FragmentDefinitionNode) {
                continue;
            }

            $fragments[] = $definition;
        }

        return $fragments;
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

        if ($type instanceof SymfonyType\ArrayShapeType) {
            $items = [];

            foreach ($type->getShape() as $key => $value) {
                $itemKey = \is_int($key) ? (string) $key : sprintf("'%s'", $key);

                if ($value['optional'] ?? false) {
                    $itemKey = sprintf('%s?', $itemKey);
                }

                $items[] = sprintf('%s: %s', $itemKey, $this->dumpPHPDocType($value['type'], $importer, $indentation + 1));
            }

            if ( ! $type->isSealed()) {
                $items[] = $type->getExtraKeyType()->isIdentifiedBy(TypeIdentifier::INT) && $type->getExtraKeyType()->isIdentifiedBy(TypeIdentifier::STRING) && $type->getExtraValueType()->isIdentifiedBy(TypeIdentifier::MIXED)
                    ? '...'
                    : sprintf('...<%s, %s>', $this->dumpPHPDocType($type->getExtraKeyType(), $importer, $indentation + 1), $this->dumpPHPDocType($type->getExtraValueType(), $importer, $indentation + 1));
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
                'array<%s, %s>',
                $this->dumpPHPDocType($type->getCollectionKeyType(), $importer, $indentation),
                $this->dumpPHPDocType($type->getCollectionKeyType(), $importer, $indentation),
            );
        }

        if ($type instanceof SymfonyType\ObjectType) {
            return $importer($type->getClassName());
        }

        return (string) $type;
    }

    /**
     * @return array{array<string, SymfonyType>, SymfonyType, SymfonyType, array}
     */
    private function parseSelectionSet(
        string $outputDirectory,
        SelectionSetNode $selectionSet,
        Type $parent,
        string $fqcn,
        ?bool $nullable = null,
    ) : array {
        if ($parent instanceof ListOfType) {
            [$fields, $payloadShape, $type, $possibleTypes] = $this->parseSelectionSet(
                $outputDirectory,
                $selectionSet,
                $parent->getWrappedType(),
                $fqcn,
                true,
            );

            return [
                SymfonyType::list($fields),
                SymfonyType::list($payloadShape),
                SymfonyType::list($type),
                $possibleTypes,
            ];
        }

        if ($parent instanceof NonNull) {
            return $this->parseSelectionSet(
                $outputDirectory,
                $selectionSet,
                $parent->getWrappedType(),
                $fqcn,
                false,
            );
        }

        if ($parent instanceof NullableType && $nullable === null) {
            [$fields, $payloadShape, $type, $possibleTypes] = $this->parseSelectionSet(
                $outputDirectory,
                $selectionSet,
                $parent,
                $fqcn,
                true,
            );

            return [
                SymfonyType::nullable($fields),
                SymfonyType::nullable($payloadShape),
                SymfonyType::nullable($type),
                $possibleTypes,
            ];
        }

        $fields = [];
        $payloadShape = [];
        $possibleTypes = [];
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $fieldName = $selection->alias?->value ?? $selection->name->value;

                if ($fieldName === '__typename') {
                    $fields[$fieldName] = SymfonyType::string();
                    $payloadShape[$fieldName] = SymfonyType::string();

                    continue;
                }

                $fieldType = $parent->getField($selection->name->value)->getType();

                $fieldTypeInnerMost = $fieldType;
                if ($fieldType instanceof WrappingType) {
                    $fieldTypeInnerMost = $fieldType->getInnermostType();
                }

                if ($selection->selectionSet !== null) {
                    $className = ucfirst($this->isList($fieldType) ? $this->inflector->singularize($fieldName) : $fieldName);

                    [$subFields, $subPayloadShape, $subType, $subPossibleTypes] = $this->parseSelectionSet(
                        $outputDirectory . '/' . $className,
                        $selection->selectionSet,
                        $fieldType,
                        $fqcn . '\\' . $className,
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
                                    'value' => $fieldTypeInnerMost->name(),
                                ]),
                            ]),
                            'selectionSet' => $selection->selectionSet,
                        ]),
                    );

                    $fields[$fieldName] = $subType;
                    $payloadShape[$fieldName] = $subPayloadShape;

                    continue;
                }

                $fields[$fieldName] = $this->mapGraphQLTypeToPHPType($fieldType);
                $payloadShape[$fieldName] = $this->mapGraphQLTypeToPHPType($fieldType, builtInOnly: true);

                continue;
            }

            if ($selection instanceof InlineFragmentNode) {
                $fieldType = $this->schema->getType($selection->typeCondition->name->value);

                $className = sprintf('As%s', $fieldType->name());
                $fieldName = sprintf('as%s', $fieldType->name());

                [$subFields, $subPayloadShape, $subType, $subPossibleTypes] = $this->parseSelectionSet(
                    $outputDirectory . '/' . $className,
                    $selection->selectionSet,
                    $fieldType,
                    $fqcn . '\\' . $className,
                );

                $this->generateDataClass(
                    $subFields instanceof SymfonyType\CollectionType && $subFields->isList() ? $subFields->getCollectionValueType() : $subFields,
                    $subPayloadShape instanceof SymfonyType\CollectionType && $subPayloadShape->isList() ? $subPayloadShape->getCollectionValueType() : $subPayloadShape,
                    [$fieldType->name()],
                    $outputDirectory,
                    $this->fullyQualified($fqcn, $className),
                    false,
                    true,
                    $selection,
                );

                $fields[$fieldName] = SymfonyType::nullable(new FragmentObjectType($this->fullyQualified($fqcn, $className), $fieldType->name()));

                foreach ($this->getNonNullableType($subPayloadShape)->getShape() as $key => $value) {
                    $payloadShape[$key] = $value;
                }

                continue;
            }

            if ($selection instanceof FragmentSpreadNode) {
                $fieldName = lcfirst($selection->name->value);
                $fields[$fieldName] = SymfonyType::nullable(new FragmentObjectType($this->fullyQualified('Fragment', $selection->name->value), $selection->name->value));

                foreach ($this->getNonNullableType($this->fragmentPayloadShapes[$selection->name->value])->getShape() as $key => $value) {
                    $payloadShape[$key] = $value;
                }
            }
        }

        return [
            SymfonyType::arrayShape($fields),
            SymfonyType::arrayShape($payloadShape),
            SymfonyType::object($fqcn),
            $possibleTypes,
        ];
    }

    // TODO MOVE TO Code Generator
    /**
     * Adds a prefix to every line of the iterable
     * @param (callable(): string)|iterable<string>|string $data
     * @return iterable<string>
     */
    public function prefix(string $prefix, callable | iterable | string $data) : iterable
    {
        foreach (\Ruudk\CodeGenerator\CodeGenerator::resolveIterable($data) as $line) {
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

    private function isObject(Type $fieldType, string $name) : bool
    {
        if ($fieldType instanceof NonNull) {
            return $this->isObject($fieldType->getWrappedType(), $name);
        }

        if ($fieldType instanceof NamedType) {
            return $fieldType->name() === $name;
        }

        return false;
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

    private function getClassName(string $fqcn) : string
    {
        return array_last(explode('\\', $fqcn));
    }

    public function getNonNullableType(SymfonyType $type) : SymfonyType
    {
        if ($type instanceof SymfonyType\NullableType) {
            return $type->getWrappedType();
        }

        return $type;
    }

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
}
