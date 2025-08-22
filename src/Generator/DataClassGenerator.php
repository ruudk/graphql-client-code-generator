<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Printer;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Webmozart\Assert\Assert;

final class DataClassGenerator extends AbstractGenerator
{
    /**
     * @param list<string> $possibleTypes
     * @param array<string, list<string>> $inlineFragmentRequiredFields
     */
    public function generate(
        NamedType & Type $parentType,
        SymfonyType $fields,
        SymfonyType $payloadShape,
        array $possibleTypes,
        string $fqcn,
        bool $isData,
        bool $isFragment,
        null | FragmentDefinitionNode | InlineFragmentNode | OperationDefinitionNode $definitionNode,
        ?SymfonyType $nodesType,
        DelegatingTypeInitializer $typeInitializer,
        array $inlineFragmentRequiredFields,
    ) : string {
        if ($fields instanceof SymfonyType\NullableType) {
            $fields = $fields->getWrappedType();
        }

        if ($payloadShape instanceof SymfonyType\NullableType) {
            $payloadShape = $payloadShape->getWrappedType();
        }

        $parts = explode('\\', $fqcn);
        $className = array_pop($parts);
        $namespace = implode('\\', $parts);

        // For inline fragments with a single possible type, use literal type for __typename
        if ($isFragment && count($possibleTypes) === 1 && $payloadShape instanceof ArrayShapeType) {
            $shape = $payloadShape->getShape();

            if (isset($shape['__typename'])) {
                // Use a StringLiteralType for the __typename
                $shape['__typename'] = new StringLiteralType($possibleTypes[0]);
                $payloadShape = SymfonyType::arrayShape($shape);
            }
        }

        $generator = new CodeGenerator($namespace);

        return $generator->dumpFile(function () use ($parentType, $nodesType, $fqcn, $definitionNode, $payloadShape, $isData, $fields, $possibleTypes, $generator, $className, $typeInitializer, $inlineFragmentRequiredFields) {
            yield $this->dumpHeader();
            yield '';

            if ($this->config->dumpDefinition && $definitionNode !== null) {
                yield from $generator->docComment(Printer::doPrint($definitionNode));
            }

            if ($this->config->addSymfonyExcludeAttribute) {
                yield $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            yield sprintf('final class %s', $generator->import($fqcn));
            yield '{';
            yield $generator->indent(
                function () use ($parentType, $nodesType, $possibleTypes, $className, $fields, $isData, $payloadShape, $generator, $typeInitializer, $inlineFragmentRequiredFields) {
                    if ($possibleTypes !== []) {
                        yield from $generator->docComment('@var list<string>');
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

                            yield from $generator->docComment(function () use ($fieldType, $generator) {
                                if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                    yield sprintf(
                                        '@var %s',
                                        $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                    );
                                }
                            });
                            yield sprintf(
                                'public %s $%s {',
                                $this->dumpPHPType($fieldType, $generator->import(...)),
                                $fieldName,
                            );
                            yield $generator->indent(function () use ($parentType, $nakedFieldType, $fieldType, $generator, $fieldName, $typeInitializer, $inlineFragmentRequiredFields) {
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
                                    // Check if we have required fields for this inline fragment
                                    $requiredFields = $inlineFragmentRequiredFields[$nakedFieldType->getClassName()] ?? [];

                                    if (count($requiredFields) > 0) {
                                        yield 'get {';
                                        yield $generator->indent(function () use ($fieldName, $nakedFieldType, $requiredFields, $generator) {
                                            yield sprintf('if (isset($this->%s)) {', $fieldName);
                                            yield $generator->indent(sprintf('return $this->%s;', $fieldName));
                                            yield '}';

                                            yield '';
                                            yield sprintf(
                                                'if ($this->data[\'__typename\'] !== %s) {',
                                                var_export($nakedFieldType->fragmentType->name(), true),
                                            );
                                            yield $generator->indent(sprintf('return $this->%s = null;', $fieldName));
                                            yield '}';

                                            foreach ($requiredFields as $requiredField) {
                                                yield '';
                                                yield sprintf('if (! array_key_exists(%s, $this->data)) {', var_export($requiredField, true));
                                                yield $generator->indent(sprintf('return $this->%s = null;', $fieldName));
                                                yield '}';
                                            }

                                            yield '';
                                            yield sprintf(
                                                'return $this->%s = new %s($this->data);',
                                                $fieldName,
                                                $generator->import($nakedFieldType->getClassName()),
                                            );
                                        });
                                        yield '}';
                                    } else {
                                        yield sprintf(
                                            'get => $this->%s ??= $this->data[\'__typename\'] === %s ? new %s($this->data) : null;',
                                            $fieldName,
                                            var_export($nakedFieldType->fragmentType->name(), true),
                                            $generator->import($nakedFieldType->getClassName()),
                                        );
                                    }

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
                                    $typeInitializer->__invoke(
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
                                yield from $generator->docComment(sprintf('@phpstan-assert-if-true !null $this->%s', $fieldName));
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

                            if ($this->config->dumpOrThrows && $fieldType instanceof SymfonyType\NullableType) {
                                $fieldType = $fieldType->getWrappedType();

                                yield '';
                                yield from $generator->docComment(function () use ($fieldType, $generator) {
                                    if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                        yield sprintf(
                                            '@var %s',
                                            $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                        );
                                    }

                                    yield '@throws NodeNotFoundException';
                                });
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
                        yield from $generator->docComment(sprintf(
                            '@var %s',
                            $this->dumpPHPDocType($nodesType, $generator->import(...)),
                        ));
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

                        yield from $generator->docComment('@var list<Error>');
                        yield 'public readonly array $errors;';
                    }

                    yield '';
                    yield from $generator->docComment(function () use ($isData, $generator, $payloadShape) {
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
                    });
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

                    if ($this->config->dumpMethods && $fields instanceof ArrayShapeType) {
                        foreach ($fields->getShape() as $fieldName => ['type' => $fieldType]) {
                            Assert::string($fieldName);

                            if ($fieldName === '__typename') {
                                continue;
                            }

                            yield '';

                            if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                yield from $generator->docComment(sprintf(
                                    '@return %s',
                                    $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                ));
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

                            if ($this->config->dumpOrThrows && $fieldType instanceof SymfonyType\NullableType) {
                                $fieldType = $fieldType->getWrappedType();

                                yield '';
                                yield from $generator->docComment(function () use ($fieldType, $generator) {
                                    if ($this->getNakedType($fieldType) instanceof SymfonyType\CollectionType) {
                                        yield sprintf(
                                            '@return %s',
                                            $this->dumpPHPDocType($fieldType, $generator->import(...)),
                                        );
                                    }

                                    yield '@throws NodeNotFoundException';
                                });
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

                    if ($isData && $this->config->dumpMethods) {
                        yield '';
                        yield from $generator->docComment('@return list<Error>');
                        yield 'public function getErrors() : array';
                        yield '{';
                        yield $generator->indent(function () {
                            yield 'return $this->errors;';
                        });
                        yield '}';
                    }

                    if ($nodesType !== null && $this->config->dumpMethods) {
                        yield '';
                        yield from $generator->docComment(sprintf(
                            '@return %s',
                            $this->dumpPHPDocType($nodesType, $generator->import(...)),
                        ));
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
    }
}
