<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQL\AST\Printer;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Source\FileSource;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Webmozart\Assert\Assert;

final class DataClassGenerator extends AbstractGenerator
{
    public function __construct(
        Config $config,
        private readonly DelegatingTypeInitializer $typeInitializer,
    ) {
        parent::__construct($config);
    }

    public function generate(DataClassPlan $plan) : string
    {
        $parentType = $plan->parentType;
        $fields = $plan->fields;
        $payloadShape = $plan->payloadShape;
        $possibleTypes = $plan->possibleTypes;
        $fqcn = $plan->fqcn;
        $isData = $plan->isData;
        $isFragment = $plan->isFragment;
        $definitionNode = $plan->definitionNode;
        $nodesType = $plan->nodesType;
        /** @var array<string, list<string>> */
        $inlineFragmentRequiredFields = $plan->inlineFragmentRequiredFields;

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

        return $generator->dumpFile(function () use ($plan, $definitionNode, $parentType, $nodesType, $fqcn, $payloadShape, $isData, $fields, $possibleTypes, $generator, $inlineFragmentRequiredFields) {
            yield $this->dumpHeader();
            yield '';

            yield from $generator->docComment(function () use ($definitionNode) {
                if ($this->config->dumpDefinition && $definitionNode !== null) {
                    yield Printer::doPrint($definitionNode);
                }
            });

            if ($this->config->addSymfonyExcludeAttribute) {
                yield from $generator->dumpAttribute('Symfony\Component\DependencyInjection\Attribute\Exclude');
            }

            if ($this->config->addGeneratedAttribute) {
                yield from $generator->dumpAttribute(Generated::class, function () use ($generator, $plan) {
                    if ($plan->source instanceof FileSource) {
                        yield sprintf('source: %s', var_export($plan->source->relativeFilePath, true));

                        return;
                    }

                    yield sprintf('source: %s', $generator->dumpClassReference($plan->source->class));
                    yield 'restricted: true';
                    yield 'restrictInstantiation: true';
                });
            }

            yield sprintf('final class %s', $generator->import($fqcn));
            yield '{';

            $requiredFieldsMap = $inlineFragmentRequiredFields;

            yield $generator->indent(
                function () use ($parentType, $nodesType, $possibleTypes, $fields, $isData, $payloadShape, $generator, $requiredFieldsMap) {
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

                    // Get optional flags and actual types from payloadShape
                    $optionalFields = [];
                    $payloadFieldTypes = [];

                    if ($payloadShape instanceof ArrayShapeType) {
                        foreach ($payloadShape->getShape() as $fieldName => $fieldValue) {
                            // ArrayShapeType always returns array format
                            if ($fieldValue['optional']) {
                                $optionalFields[$fieldName] = true;
                            }

                            $payloadFieldTypes[$fieldName] = $fieldValue['type'];
                        }
                    }

                    if ($fields instanceof ArrayShapeType) {
                        $shape = $fields->getShape();

                        foreach ($shape as $fieldName => $fieldValue) {
                            Assert::string($fieldName);

                            // ArrayShapeType always returns arrays with type and optional
                            $fieldType = $fieldValue['type'];
                            $optional = $fieldValue['optional'];

                            // Check if field is optional in payloadShape
                            $optional = $optional || isset($optionalFields[$fieldName]);

                            $nakedFieldType = $this->getNakedType($fieldType);

                            yield '';

                            // Check the payload shape to properly handle nullability
                            $payloadType = $payloadFieldTypes[$fieldName] ?? null;

                            // Use field type as the base, but incorporate nullability from payload if needed
                            $propertyType = $fieldType;

                            // If payload type is nullable but field type isn't, make property nullable
                            if ($payloadType instanceof SymfonyType\NullableType && ! ($fieldType instanceof SymfonyType\NullableType)) {
                                $propertyType = SymfonyType::nullable($fieldType);
                            }

                            // For optional fields, the property must be nullable (since field might not exist)
                            if ($optional && ! ($propertyType instanceof SymfonyType\NullableType)) {
                                $propertyType = SymfonyType::nullable($propertyType);
                            }

                            yield from $generator->docComment(function () use ($propertyType, $generator) {
                                if ($this->getNakedType($propertyType) instanceof SymfonyType\CollectionType) {
                                    yield sprintf(
                                        '@var %s',
                                        TypeDumper::dump($propertyType, $generator->import(...)),
                                    );
                                }
                            });
                            yield sprintf(
                                'public %s $%s {',
                                $this->dumpPHPType($propertyType, $generator->import(...)),
                                $fieldName,
                            );
                            yield $generator->indent(function () use ($parentType, $nakedFieldType, $fieldType, $generator, $fieldName, $requiredFieldsMap, $optional, $payloadFieldTypes, $propertyType) {
                                if ($nakedFieldType instanceof FragmentObjectType) {
                                    // For interface/union parent types, we need special handling
                                    if ( ! $parentType instanceof ObjectType) {
                                        // Check if we have required fields for this fragment
                                        $fragmentClassName = $nakedFieldType->getClassName();
                                        /** @var list<string> $requiredFields */
                                        $requiredFields = $requiredFieldsMap[$fragmentClassName] ?? [];

                                        // For fragments on interface/union types themselves
                                        if ($nakedFieldType->fragmentType instanceof InterfaceType || $nakedFieldType->fragmentType instanceof UnionType) {
                                            yield sprintf(
                                                'get => $this->%s ??= in_array($this->data[\'__typename\'], %s::POSSIBLE_TYPES, true) ? new %s($this->data) : null;',
                                                $fieldName,
                                                $generator->import($nakedFieldType->getClassName()),
                                                $generator->import($nakedFieldType->getClassName()),
                                            );

                                            return;
                                        }

                                        // For fragments on concrete types when parent is interface/union
                                        // We need to check both typename and required fields
                                        if ($requiredFields === []) {
                                            // No required fields to check, only typename
                                            yield sprintf(
                                                'get => $this->%s ??= $this->data[\'__typename\'] === %s ? new %s($this->data) : null;',
                                                $fieldName,
                                                var_export($nakedFieldType->fragmentType->name(), true),
                                                $generator->import($nakedFieldType->getClassName()),
                                            );
                                        } else {
                                            // Generate verbose getter with field checks for PHPStan type safety
                                            yield 'get {';
                                            yield $generator->indent(function () use ($fieldName, $nakedFieldType, $generator, $requiredFields) {
                                                yield sprintf('if (isset($this->%s)) {', $fieldName);
                                                yield '    return $this->' . $fieldName . ';';
                                                yield '}';
                                                yield '';
                                                yield sprintf(
                                                    'if ($this->data[\'__typename\'] !== %s) {',
                                                    var_export($nakedFieldType->fragmentType->name(), true),
                                                );
                                                yield '    return $this->' . $fieldName . ' = null;';
                                                yield '}';

                                                // Check all required fields exist
                                                foreach ($requiredFields as $requiredField) {
                                                    yield '';
                                                    yield sprintf(
                                                        'if (! array_key_exists(%s, $this->data)) {',
                                                        var_export($requiredField, true),
                                                    );
                                                    yield '    return $this->' . $fieldName . ' = null;';
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
                                        }

                                        return;
                                    }

                                    // For ObjectType parents, fragments don't need field checking
                                    yield sprintf(
                                        'get => $this->%s ??= new %s($this->data);',
                                        $fieldName,
                                        $generator->import($nakedFieldType->getClassName()),
                                    );

                                    return;
                                }

                                // Handle optional fields (from @include/@skip directives)
                                if ($optional) {
                                    // Optional field - need array_key_exists check

                                    // Check the payload shape to determine if field is nullable
                                    $payloadType = $payloadFieldTypes[$fieldName] ?? null;
                                    $isPayloadNullable = $payloadType instanceof SymfonyType\NullableType;

                                    yield 'get {';
                                    yield $generator->indent(function () use ($fieldName, $fieldType, $generator, $isPayloadNullable) {
                                        yield sprintf('if (isset($this->%s)) {', $fieldName);
                                        yield '    return $this->' . $fieldName . ';';
                                        yield '}';
                                        yield '';
                                        yield sprintf(
                                            'if (! array_key_exists(%s, $this->data)) {',
                                            var_export($fieldName, true),
                                        );
                                        yield '    return $this->' . $fieldName . ' = null;';
                                        yield '}';

                                        // Only check for null if the payload field is nullable
                                        if ($isPayloadNullable) {
                                            yield '';
                                            yield sprintf(
                                                'if ($this->data[%s] === null) {',
                                                var_export($fieldName, true),
                                            );
                                            yield '    return $this->' . $fieldName . ' = null;';
                                            yield '}';
                                        }

                                        yield '';
                                        // Always use the non-nullable type for initialization
                                        // since we've already handled the null case if needed
                                        $typeForInit = $isPayloadNullable && $fieldType instanceof SymfonyType\NullableType
                                            ? $fieldType->getWrappedType()
                                            : $fieldType;

                                        yield from $generator->wrap(
                                            sprintf('return $this->%s = ', $fieldName),
                                            $this->typeInitializer->__invoke(
                                                $typeForInit,
                                                $generator,
                                                sprintf('$this->data[%s]', var_export($fieldName, true)),
                                            ),
                                            ';',
                                        );
                                    });
                                    yield '}';

                                    return;
                                }

                                // Check if this is a multi-field IndexByCollectionType
                                $isMultiFieldIndexBy = false;

                                if ($propertyType instanceof IndexByCollectionType) {
                                    $isMultiFieldIndexBy = $propertyType->value instanceof IndexByCollectionType;
                                }

                                if ($isMultiFieldIndexBy) {
                                    yield from $generator->wrap(
                                        sprintf(
                                            'get => $this->%s ??= ',
                                            $fieldName,
                                        ),
                                        $this->typeInitializer->__invoke(
                                            $propertyType,
                                            $generator,
                                            sprintf('$this->data[%s]', var_export($fieldName, true)),
                                        ),
                                        ';',
                                    );
                                } else {
                                    yield from $generator->wrap(
                                        sprintf(
                                            'get => $this->%s ??= ',
                                            $fieldName,
                                        ),
                                        $this->typeInitializer->__invoke(
                                            $propertyType,
                                            $generator,
                                            sprintf('$this->data[%s]', var_export($fieldName, true)),
                                        ),
                                        ';',
                                    );
                                }
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

                            if ($this->config->dumpOrThrows && $propertyType instanceof SymfonyType\NullableType) {
                                $unwrappedType = $propertyType->getWrappedType();

                                yield '';
                                yield from $generator->docComment(function () use ($unwrappedType, $generator) {
                                    if ($this->getNakedType($unwrappedType) instanceof SymfonyType\CollectionType) {
                                        yield sprintf(
                                            '@var %s',
                                            TypeDumper::dump($unwrappedType, $generator->import(...)),
                                        );
                                    }

                                    yield sprintf('@throws %s', $generator->import($this->fullyQualified('NodeNotFoundException')));
                                });
                                yield sprintf(
                                    'public %s $%sOrThrow {',
                                    $this->dumpPHPType($unwrappedType, $generator->import(...)),
                                    $fieldName,
                                );
                                yield $generator->indent(function () use ($parentType, $generator, $fieldName) {
                                    yield sprintf(
                                        'get => $this->%s ?? throw %s::create(%s, %s);',
                                        $fieldName,
                                        $generator->import($this->fullyQualified('NodeNotFoundException')),
                                        var_export($parentType->name(), true),
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
                            TypeDumper::dump($nodesType, $generator->import(...)),
                        ));
                        yield sprintf(
                            'public %s $nodes {',
                            $this->dumpPHPType($nodesType, $generator->import(...)),
                        );

                        // Check if edges field has multi-field indexing
                        $isMultiFieldIndexedEdges = false;

                        if ($fields instanceof ArrayShapeType) {
                            $edgesFieldType = $fields->getShape()['edges']['type'] ?? null;

                            if ($edgesFieldType instanceof IndexByCollectionType) {
                                $isMultiFieldIndexedEdges = $edgesFieldType->value instanceof IndexByCollectionType;
                            }
                        }

                        if ($isMultiFieldIndexedEdges) {
                            // For multi-field indexed edges, we need nested loops
                            yield $generator->indent(function () use ($generator) {
                                yield 'get {';
                                yield $generator->indent(function () use ($generator) {
                                    yield '$nodes = [];';
                                    yield 'foreach ($this->edges as $edgeGroup) {';
                                    yield $generator->indent(function () use ($generator) {
                                        yield 'foreach ($edgeGroup as $edge) {';
                                        yield $generator->indent(function () {
                                            yield '$nodes[] = $edge->node;';
                                        });
                                        yield '}';
                                    });
                                    yield '}';

                                    yield '';
                                    yield 'return $nodes;';
                                });
                                yield '}';
                            });
                        } else {
                            yield $generator->indent(sprintf(
                                'get => array_map(fn($edge) => $edge->node, $this->edges);',
                            ));
                        }

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
                            TypeDumper::dump($payloadShape, $generator->import(...)),
                        );

                        if ($isData) {
                            yield sprintf(
                                '@param %s $errors',
                                TypeDumper::dump(SymfonyType::list(SymfonyType::arrayShape([
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
                },
            );
            yield '}';
        });
    }
}
