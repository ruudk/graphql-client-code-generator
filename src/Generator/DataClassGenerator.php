<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use GraphQL\Language\Printer;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
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

        return $generator->dumpFile(function () use ($parentType, $nodesType, $fqcn, $definitionNode, $payloadShape, $isData, $fields, $possibleTypes, $generator, $inlineFragmentRequiredFields) {
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
                                        $this->dumpPHPDocType($propertyType, $generator->import(...)),
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
                                            $this->dumpPHPDocType($unwrappedType, $generator->import(...)),
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
                        foreach ($fields->getShape() as $fieldName => $fieldValue) {
                            Assert::string($fieldName);

                            if ($fieldName === '__typename') {
                                continue;
                            }

                            // Get the field type and check if it's optional
                            $fieldType = $fieldValue['type'];
                            $optional = $fieldValue['optional'];

                            // Check if field is optional in payloadShape
                            $optional = $optional || isset($optionalFields[$fieldName]);

                            // For optional fields, make the return type nullable if it isn't already
                            $dumpMethodType = $optional && ! ($fieldType instanceof SymfonyType\NullableType)
                                ? SymfonyType::nullable($fieldType)
                                : $fieldType;

                            yield '';

                            if ($this->getNakedType($dumpMethodType) instanceof SymfonyType\CollectionType) {
                                yield from $generator->docComment(sprintf(
                                    '@return %s',
                                    $this->dumpPHPDocType($dumpMethodType, $generator->import(...)),
                                ));
                            }

                            yield sprintf(
                                'public function %s() : %s',
                                $this->getterMethod($fieldName),
                                $this->dumpPHPType($dumpMethodType, $generator->import(...)),
                            );
                            yield '{';
                            yield $generator->indent(function () use ($fieldName) {
                                yield sprintf('return $this->%s;', $fieldName);
                            });
                            yield '}';

                            if ($this->config->dumpOrThrows && $dumpMethodType instanceof SymfonyType\NullableType) {
                                $unwrappedType = $dumpMethodType->getWrappedType();

                                yield '';
                                yield from $generator->docComment(function () use ($unwrappedType, $generator) {
                                    if ($this->getNakedType($unwrappedType) instanceof SymfonyType\CollectionType) {
                                        yield sprintf(
                                            '@return %s',
                                            $this->dumpPHPDocType($unwrappedType, $generator->import(...)),
                                        );
                                    }

                                    yield sprintf('@throws %s', $generator->import($this->fullyQualified('NodeNotFoundException')));
                                });
                                yield sprintf(
                                    'public function %sOrThrow() : %s',
                                    $this->getterMethod($fieldName),
                                    $this->dumpPHPType($unwrappedType, $generator->import(...)),
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
