<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Generator;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\CodeGenerator\Group;
use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQL\AST\Printer;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\Narrowing;
use Ruudk\GraphQLCodeGenerator\Planner\Source\GraphQLFileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\HookInputSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\TwigFileSource;
use Ruudk\GraphQLCodeGenerator\Type\FlattenedPropertyType;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\GroupByCollectionType;
use Ruudk\GraphQLCodeGenerator\Type\HookPropertyType;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
use Ruudk\GraphQLCodeGenerator\Type\ThrowWhenNullPropertyType;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final class DataClassGenerator extends AbstractGenerator
{
    public function __construct(
        SchemaConfig $config,
        private readonly DelegatingTypeInitializer $typeInitializer,
    ) {
        parent::__construct($config);
    }

    /**
     * Delegates fragment-spread construction to the type initializer so the
     * single source of truth (`ObjectTypeInitializer` + `ClassHookUsageRegistry`)
     * decides whether to forward `$this->hooks` into the child constructor.
     *
     * @throws InvalidArgumentException
     */
    private function initializeFragmentObject(FragmentObjectType $type, CodeGenerator $generator): string
    {
        $result = $this->typeInitializer->__invoke($type, $generator, '$this->data');
        Assert::string($result);

        return $result;
    }

    /**
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     */
    private function dumpPossibleTypesList(array $plansByFqcn, string $fqcn): string
    {
        Assert::keyExists($plansByFqcn, $fqcn);

        return implode(', ', array_map(static fn(string $type) => var_export(
            $type,
            true,
        ), $plansByFqcn[$fqcn]->possibleTypes));
    }

    /**
     * Name of the generated method that walks the typed object graph collecting
     * a batched hook's data objects. Hook names are validated as PHP identifiers
     * by `SchemaConfig::withHook()`.
     */
    private function collectMethodName(string $hookName): string
    {
        return 'collectHook' . ucfirst($hookName) . 'Inputs';
    }

    /**
     * Name of the generated method that builds a hook's `requires` data object
     * from `$this->data`.
     */
    private function buildMethodName(HookPropertyType $hook): string
    {
        return 'build' . $hook->requiresClassName;
    }

    /**
     * PHPDoc `array{hookName: HookLoader<DataClass, V>, ...}` shape for the
     * `$loaders` argument/property. `DataClass` is the hook's `requires` data
     * class and `V` its (unwrapped) return value type.
     *
     * @param array<string, true> $batchedHooks
     */
    private function dumpLoadersShape(array $batchedHooks, CodeGenerator $generator): string
    {
        $hookLoader = $generator->import($this->fullyQualified('HookLoader'));
        $entries = [];

        foreach (array_keys($batchedHooks) as $name) {
            $hook = $this->config->hooks[$name];
            $entries[] = sprintf(
                '%s: %s<%s, %s>',
                $name,
                $hookLoader,
                $generator->import($hook->requiresFqcn),
                TypeDumper::dump($hook->returnType, $generator->import(...), 1),
            );
        }

        // Unsealed: nested classes receive `$this->loaders` from the Data class,
        // which holds every batched hook in the operation, not just the subset a
        // single nested class consumes. Extras are typed wide; HookLoader's
        // covariant template params let any specific instantiation fit here.
        $entries[] = sprintf('...<string, %s<mixed, mixed>>', $hookLoader);

        return sprintf("array{\n    %s,\n}", implode(",\n    ", $entries));
    }

    /**
     * Replicates the getter's property-type computation: a field's PHP property
     * type is its field type made nullable when the payload is nullable or the
     * field is optional. Used to emit correctly null-guarded collect traversals.
     */
    private function effectivePropertyType(
        SymfonyType $fieldType,
        ?SymfonyType $payloadType,
        bool $optional,
    ): SymfonyType {
        $propertyType = $fieldType;

        if ($payloadType instanceof SymfonyType\NullableType && !$fieldType instanceof SymfonyType\NullableType) {
            $propertyType = SymfonyType::nullable($fieldType);
        }

        if ($optional && !$propertyType instanceof SymfonyType\NullableType) {
            $propertyType = SymfonyType::nullable($propertyType);
        }

        return $propertyType;
    }

    /**
     * Peel nullable/collection/throw-when-null wrappers; return the FQCN of the
     * nested ObjectType if that is what remains. Mirrors `Planner::unwrapToObjectType`
     * so the collect traversal reaches exactly the classes hook propagation marked.
     */
    private function unwrapToObjectClassName(SymfonyType $type): ?string
    {
        while (true) {
            if ($type instanceof ThrowWhenNullPropertyType || $type instanceof FlattenedPropertyType) {
                $type = $type->getWrappedType();

                continue;
            }

            if ($type instanceof SymfonyType\NullableType) {
                $type = $type->getWrappedType();

                continue;
            }

            if ($type instanceof SymfonyType\CollectionType) {
                $type = $type->getCollectionValueType();

                continue;
            }

            break;
        }

        return $type instanceof SymfonyType\ObjectType ? $type->getClassName() : null;
    }

    /**
     * Emit the structural descent into one field: null guards for nullable
     * values, `foreach` for collections, recursing into the child class body
     * once a generated object is reached.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     * @return iterable<string|Group>
     */
    private function emitCollectDescent(
        string $accessor,
        SymfonyType $type,
        DataClassPlan $childPlan,
        string $hookName,
        array $plansByFqcn,
        CodeGenerator $generator,
        int $depth,
    ): iterable {
        if ($type instanceof ThrowWhenNullPropertyType || $type instanceof FlattenedPropertyType) {
            yield from $this->emitCollectDescent(
                $accessor,
                $type->getWrappedType(),
                $childPlan,
                $hookName,
                $plansByFqcn,
                $generator,
                $depth,
            );

            return;
        }

        if ($type instanceof SymfonyType\NullableType) {
            yield sprintf('if (%s !== null) {', $accessor);
            yield $generator->indent(function () use (
                $accessor,
                $type,
                $childPlan,
                $hookName,
                $plansByFqcn,
                $generator,
                $depth,
            ) {
                yield from $this->emitCollectDescent(
                    $accessor,
                    $type->getWrappedType(),
                    $childPlan,
                    $hookName,
                    $plansByFqcn,
                    $generator,
                    $depth,
                );
            });
            yield '}';

            return;
        }

        if ($type instanceof SymfonyType\CollectionType) {
            $item = '$item' . ($depth > 0 ? (string) $depth : '');
            yield sprintf('foreach (%s as %s) {', $accessor, $item);
            yield $generator->indent(function () use (
                $item,
                $type,
                $childPlan,
                $hookName,
                $plansByFqcn,
                $generator,
                $depth,
            ) {
                yield from $this->emitCollectDescent(
                    $item,
                    $type->getCollectionValueType(),
                    $childPlan,
                    $hookName,
                    $plansByFqcn,
                    $generator,
                    $depth + 1,
                );
            });
            yield '}';

            return;
        }

        if ($type instanceof SymfonyType\ObjectType) {
            yield from $this->emitCollectClassBody($accessor, $childPlan, $hookName, $plansByFqcn, $generator, $depth);
        }
    }

    /**
     * Emit the body that walks one class's typed properties collecting a
     * batched hook's `[$owner, $inputTuple]` pairs — its own hook field first,
     * then a recursive descent into each child that reaches the hook. `$accessor`
     * is the expression holding the current class instance.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     * @return iterable<string|Group>
     */
    private function emitCollectClassBody(
        string $accessor,
        DataClassPlan $plan,
        string $hookName,
        array $plansByFqcn,
        CodeGenerator $generator,
        int $depth,
    ): iterable {
        $fields = $plan->fields;

        if ($fields instanceof SymfonyType\NullableType) {
            $fields = $fields->getWrappedType();
        }

        if (!$fields instanceof ArrayShapeType) {
            return;
        }

        $payloadShape = $plan->payloadShape;

        if ($payloadShape instanceof SymfonyType\NullableType) {
            $payloadShape = $payloadShape->getWrappedType();
        }

        $payloadFieldTypes = [];
        $optionalFields = [];

        if ($payloadShape instanceof ArrayShapeType) {
            foreach ($payloadShape->getShape() as $payloadName => $payloadValue) {
                if ($payloadValue['optional']) {
                    $optionalFields[$payloadName] = true;
                }

                $payloadFieldTypes[$payloadName] = $payloadValue['type'];
            }
        }

        foreach ($fields->getShape() as $fieldName => $fieldValue) {
            Assert::string($fieldName);
            $fieldType = $fieldValue['type'];

            if ($fieldType instanceof HookPropertyType) {
                if ($fieldType->hookName === $hookName) {
                    yield sprintf('yield [%s, %s->%s()];', $accessor, $accessor, $this->buildMethodName($fieldType));
                }

                continue;
            }

            if ($fieldType instanceof ThrowWhenNullPropertyType || $fieldType instanceof FlattenedPropertyType) {
                $propertyType = $fieldType->getWrappedType();
            } else {
                $optional = $fieldValue['optional'] || isset($optionalFields[$fieldName]);
                $propertyType = $this->effectivePropertyType(
                    $fieldType,
                    $payloadFieldTypes[$fieldName] ?? null,
                    $optional,
                );
            }

            $leafFqcn = $this->unwrapToObjectClassName($propertyType);

            if (
                $leafFqcn === null
                || !isset($plansByFqcn[$leafFqcn])
                || !isset($plansByFqcn[$leafFqcn]->usedHooks[$hookName])
            ) {
                continue;
            }

            yield from $this->emitCollectDescent(
                $accessor . '->' . $fieldName,
                $propertyType,
                $plansByFqcn[$leafFqcn],
                $hookName,
                $plansByFqcn,
                $generator,
                $depth,
            );
        }
    }

    /**
     * Emit one `private collectHook<Name>Inputs()` method per batched hook,
     * only on the operation's `Data` class. The method inlines the full walk of
     * the typed object graph, yielding `[$owner, $dataObject]` pairs for the
     * `HookLoader` to batch. Keeping the walk on `Data` lets it stay private
     * and leaves nested classes free of generated traversal methods.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     * @return iterable<string|Group>
     */
    private function dumpCollectMethods(DataClassPlan $plan, array $plansByFqcn, CodeGenerator $generator): iterable
    {
        if (!$plan->isData) {
            return;
        }

        foreach (array_keys($plan->usedHooks) as $hookName) {
            if (!$this->config->hooks[$hookName]->batched) {
                continue;
            }

            yield '';
            yield from $generator->docComment(sprintf(
                '@return iterable<array{object, %s}>',
                $generator->import($this->config->hooks[$hookName]->requiresFqcn),
            ));
            yield sprintf('private function %s() : iterable', $this->collectMethodName($hookName));
            yield '{';
            yield $generator->indent(function () use ($plan, $hookName, $plansByFqcn, $generator) {
                yield from $this->emitCollectClassBody('$this', $plan, $hookName, $plansByFqcn, $generator, 0);
            });
            yield '}';
        }
    }

    /**
     * Emit a `build<RequiresClass>()` method for each `@hook` field on this class.
     * It constructs the hook's typed data object from `$this->data`. Public because
     * the `Data` class's collect walk calls it on nested instances.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @return iterable<string|Group>
     */
    private function dumpHookBuildMethods(DataClassPlan $plan, array $plansByFqcn, CodeGenerator $generator): iterable
    {
        $fields = $plan->fields;

        if ($fields instanceof SymfonyType\NullableType) {
            $fields = $fields->getWrappedType();
        }

        if (!$fields instanceof ArrayShapeType) {
            return;
        }

        $emitted = [];

        foreach ($fields->getShape() as $fieldValue) {
            $fieldType = $fieldValue['type'];

            if (!$fieldType instanceof HookPropertyType || isset($emitted[$fieldType->requiresClassName])) {
                continue;
            }

            $emitted[$fieldType->requiresClassName] = true;
            $dataClass = $generator->import($fieldType->requiresFqcn);

            // The data class takes `$decoders` when it decodes a scalar
            $decoders = ($plansByFqcn[$fieldType->requiresFqcn]->usedDecoders ?? []) !== [] ? ', $this->decoders' : '';

            yield '';
            yield from $generator->docComment('@internal');
            yield sprintf('public function %s() : %s', $this->buildMethodName($fieldType), $dataClass);
            yield '{';
            yield $generator->indent(sprintf('return new %s($this->data%s);', $dataClass, $decoders));
            yield '}';
        }
    }

    /**
     * Maps grouped edges to grouped nodes, keeping every level: one `array_map` per level, around the
     * one that turns a group of edges into a group of nodes.
     */
    private function dumpGroupedNodes(int $levels, string $groups): string
    {
        if ($levels === 1) {
            return sprintf('array_map(fn($edges) => array_map(fn($edge) => $edge->node, $edges), %s)', $groups);
        }

        return sprintf('array_map(fn($group) => %s, %s)', $this->dumpGroupedNodes($levels - 1, '$group'), $groups);
    }

    /**
     * The hooks of a deprecated property read the property itself, which Mago reports as a use
     * of a deprecated property. Expect that issue in the hook's doc comment, so it only shows up
     * where the caller reads it.
     *
     * @return iterable<string>
     */
    private function dumpDeprecatedPropertyRead(?string $reason): iterable
    {
        if ($reason !== null) {
            yield '@mago-expect analysis:deprecated-property';
        }
    }

    /**
     * Emit a property lifted onto this class by `@flatten`. It reads its value
     * from a path through the flattened objects, guarding every nullable object
     * on the way: a plain nullable object yields null, one tagged
     * `@throwWhenNull` throws `NodeNotFoundException`.
     *
     * @throws InvalidArgumentException
     * @return iterable<string|Group>
     */
    private function dumpFlattenedProperty(
        string $fieldName,
        FlattenedPropertyType $type,
        bool $throws,
        string $parentTypeName,
        bool $isMutationData,
        CodeGenerator $generator,
        ?string $deprecationReason,
    ): iterable {
        $propertyType = $type->getWrappedType();

        yield from $generator->docComment(function () use (
            $propertyType,
            $isMutationData,
            $generator,
            $deprecationReason,
        ) {
            if ($isMutationData) {
                yield '@api';
            }

            if ($this->getNakedType($propertyType) instanceof SymfonyType\CollectionType) {
                yield sprintf('@var %s', TypeDumper::dump($propertyType, $generator->import(...)));
            }

            yield from $this->dumpDeprecation($deprecationReason);
        });
        yield sprintf('public %s $%s {', $this->dumpPHPType($propertyType, $generator->import(...)), $fieldName);
        yield $generator->indent(function () use ($fieldName, $type, $throws, $generator, $deprecationReason) {
            yield from $generator->docComment(function () use ($generator, $throws, $deprecationReason) {
                if ($throws) {
                    yield sprintf('@throws %s', $generator->import($this->fullyQualified('NodeNotFoundException')));
                }

                yield from $this->dumpDeprecatedPropertyRead($deprecationReason);
            });
            yield from $generator->wrap(
                sprintf('get => $this->%s ??= ', $fieldName),
                $this->flattenedValue($type, $generator),
                ';',
            );
        });
        yield '}';

        if ($this->config->dumpOrThrowProperties && $propertyType instanceof SymfonyType\NullableType) {
            $unwrappedType = $propertyType->getWrappedType();

            yield '';
            yield from $generator->docComment(function () use ($unwrappedType, $generator, $deprecationReason) {
                if ($this->getNakedType($unwrappedType) instanceof SymfonyType\CollectionType) {
                    yield sprintf('@var %s', TypeDumper::dump($unwrappedType, $generator->import(...)));
                }

                yield sprintf('@throws %s', $generator->import($this->fullyQualified('NodeNotFoundException')));
                yield from $this->dumpDeprecation($deprecationReason);
            });
            yield sprintf(
                'public %s $%sOrThrow {',
                $this->dumpPHPType($unwrappedType, $generator->import(...)),
                $fieldName,
            );
            yield $generator->indent(function () use ($generator, $fieldName, $parentTypeName, $deprecationReason) {
                yield from $generator->docComment($this->dumpDeprecatedPropertyRead($deprecationReason));
                yield sprintf(
                    'get => $this->%s ?? throw %s::create(%s, %s);',
                    $fieldName,
                    $generator->import($this->fullyQualified('NodeNotFoundException')),
                    var_export($parentTypeName, true),
                    var_export($fieldName, true),
                );
            });
            yield '}';
        }
    }

    /**
     * The constructor body of a class narrowed with `@narrow`: throw when the payload is
     * another type, or misses a field the narrowed type always has, before storing it.
     *
     * @return iterable<string|Group>
     */
    private function dumpNarrowingCheck(Narrowing $narrowing, CodeGenerator $generator): iterable
    {
        $conditions = [sprintf('$data[\'__typename\'] !== %s', var_export($narrowing->typeName, true))];

        foreach ($narrowing->requiredFields as $requiredField) {
            $conditions[] = sprintf('! array_key_exists(%s, $data)', var_export($requiredField, true));
        }

        yield sprintf('if (%s) {', implode(' || ', $conditions));
        yield $generator->indent(sprintf(
            'throw %s::unexpectedType(%s, %s, %s, $data[\'__typename\']);',
            $generator->import($this->fullyQualified('NodeNotFoundException')),
            var_export($narrowing->ownerTypeName, true),
            var_export($narrowing->fieldName, true),
            var_export($narrowing->typeName, true),
        ));
        yield '}';
        yield '';
        yield '$this->data = $data;';
    }

    /**
     * Whether reading a property of this type constructs a class narrowed with `@narrow`,
     * which throws when the server returned another type.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     */
    private function constructsNarrowedClass(SymfonyType $type, array $plansByFqcn): bool
    {
        $className = $this->unwrapToObjectClassName($type);

        return $className !== null && ($plansByFqcn[$className] ?? null)?->narrowing !== null;
    }

    /**
     * Build the expression that reads a flattened property, e.g.
     * `$this->data['viewer'] !== null ? $this->data['viewer']['login'] : null`.
     * Consecutive nullable objects that fall back to the same value share one
     * condition; the innermost value is initialized like any other field.
     *
     * @throws InvalidArgumentException
     * @return Generator<string|Group>|string
     */
    private function flattenedValue(FlattenedPropertyType $type, CodeGenerator $generator): Generator|string
    {
        $paths = [];
        $path = '$this->data';

        foreach ($type->segments as $segment) {
            $path .= sprintf('[%s]', var_export($segment->key, true));
            $paths[] = $path;
        }

        $valuePath = $type->leafKey === null ? $path : sprintf('%s[%s]', $path, var_export($type->leafKey, true));
        $innerType = $type->innerType;

        // Whether $value is a ternary already, so wrapping it in another one gets parentheses.
        $conditional = $innerType instanceof SymfonyType\NullableType;

        if ($innerType instanceof ThrowWhenNullPropertyType) {
            $conditional = true;
            $innermost = $type->segments[count($type->segments) - 1];

            $value = $generator->wrap(
                sprintf('%s !== null ? ', $valuePath),
                $this->typeInitializer->__invoke($innerType->getWrappedType(), $generator, $valuePath),
                sprintf(
                    ' : throw %s::create(%s, %s)',
                    $generator->import($this->fullyQualified('NodeNotFoundException')),
                    var_export($innermost->objectTypeName, true),
                    var_export($type->leafKey, true),
                ),
            );
        } else {
            $value = $this->typeInitializer->__invoke($innerType, $generator, $valuePath);
        }

        // Group consecutive nullable objects that share the same fallback, outermost first.
        /** @var list<array{non-empty-list<string>, string}> $groups */
        $groups = [];

        foreach ($type->segments as $index => $segment) {
            if (!$segment->nullable) {
                continue;
            }

            $fallback = $segment->throwWhenNull
                ? sprintf(
                    'throw %s::create(%s, %s)',
                    $generator->import($this->fullyQualified('NodeNotFoundException')),
                    var_export($segment->ownerTypeName, true),
                    var_export($segment->key, true),
                )
                : 'null';

            $condition = sprintf('%s !== null', $paths[$index]);
            $last = count($groups) - 1;

            if ($last >= 0 && $groups[$last][1] === $fallback) {
                $groups[$last] = [[...$groups[$last][0], $condition], $fallback];

                continue;
            }

            $groups[] = [[$condition], $fallback];
        }

        foreach (array_reverse($groups) as [$conditions, $fallback]) {
            $value = $generator->wrap(
                implode(' && ', $conditions) . ($conditional ? ' ? (' : ' ? '),
                $value,
                ($conditional ? ') : ' : ' : ') . $fallback,
            );
            $conditional = true;
        }

        return $value;
    }

    /**
     * @param array<string, DataClassPlan> $plansByFqcn
     * @param array<string, string> $deprecatedFields Deprecation reason by property name
     */
    public function generate(DataClassPlan $plan, array $plansByFqcn = [], array $deprecatedFields = []): string
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

        // First-level fields of a mutation's Data class are mutated as a
        // side effect of execute()/executeOrThrow(), regardless of whether
        // the caller reads them. Tag them @api so dead-code analysis does
        // not flag the properties as unused.
        $isMutationData =
            $isData && $definitionNode instanceof OperationDefinitionNode && $definitionNode->operation === 'mutation';

        $inlineFragmentRequiredFields = $plan->inlineFragmentRequiredFields;

        if ($fields instanceof SymfonyType\NullableType) {
            $fields = $fields->getWrappedType();
        }

        if ($payloadShape instanceof SymfonyType\NullableType) {
            $payloadShape = $payloadShape->getWrappedType();
        }

        $parts = explode('\\', $fqcn);
        array_pop($parts);
        $namespace = implode('\\', $parts);

        // For inline fragments with a single possible type, use literal type for __typename
        if ($isFragment && count($possibleTypes) === 1 && $payloadShape instanceof ArrayShapeType) {
            $shape = $payloadShape->getShape();

            if (isset($shape['__typename'])) {
                // Use a StringLiteralType for the __typename
                $shape['__typename'] = new StringLiteralType($possibleTypes[0]);
                $payloadShape = SymfonyType::arrayShape($shape, sealed: false);
            }
        }

        $generator = new CodeGenerator($namespace);

        return $generator->dumpFile(function () use (
            $plan,
            $definitionNode,
            $parentType,
            $nodesType,
            $fqcn,
            $payloadShape,
            $isData,
            $isMutationData,
            $fields,
            $generator,
            $inlineFragmentRequiredFields,
            $plansByFqcn,
            $deprecatedFields,
        ) {
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
                yield from $generator->dumpAttribute(Generated::class, static function () use ($generator, $plan) {
                    if ($plan->source instanceof GraphQLFileSource) {
                        yield sprintf('source: %s', var_export($plan->source->relativeFilePath, true));

                        return;
                    }

                    if ($plan->source instanceof TwigFileSource) {
                        yield sprintf('source: %s', var_export($plan->source->relativeFilePath, true));
                        yield 'restricted: true';
                        yield 'restrictInstantiation: true';

                        return;
                    }

                    if ($plan->source instanceof HookInputSource) {
                        yield sprintf('source: %s', var_export('hook:' . $plan->source->hookName, true));

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

            yield $generator->indent(function () use (
                $plan,
                $parentType,
                $nodesType,
                $fields,
                $isData,
                $isMutationData,
                $payloadShape,
                $generator,
                $requiredFieldsMap,
                $plansByFqcn,
                $deprecatedFields,
            ) {
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

                        // Hook-backed synthetic property: not stored in $this->data, resolved
                        // at access time by invoking the user-supplied hook with the typed
                        // data object built from the hook's `requires` fragment.
                        if ($fieldType instanceof HookPropertyType) {
                            $wrappedReturnType = $fieldType->getWrappedType();

                            yield from $generator->docComment(function () use (
                                $wrappedReturnType,
                                $isMutationData,
                                $generator,
                            ) {
                                if ($isMutationData) {
                                    yield '@api';
                                }

                                if ($this->getNakedType($wrappedReturnType) instanceof SymfonyType\CollectionType) {
                                    yield sprintf('@var %s', TypeDumper::dump(
                                        $wrappedReturnType,
                                        $generator->import(...),
                                    ));
                                }
                            });
                            yield sprintf(
                                'public %s $%s {',
                                $this->dumpPHPType($fieldType, $generator->import(...)),
                                $fieldName,
                            );
                            yield $generator->indent(function () use ($fieldType, $fieldName) {
                                // Batched hook: delegate to the per-operation HookLoader,
                                // which resolves the whole batch once and looks this
                                // instance up by object identity.
                                if ($fieldType->batched) {
                                    yield sprintf(
                                        'get => $this->%s ??= $this->loaders[%s]->resolve($this);',
                                        $fieldName,
                                        var_export($fieldType->hookName, true),
                                    );

                                    return;
                                }

                                yield sprintf(
                                    'get => $this->%s ??= $this->hooks[%s]->__invoke($this->%s());',
                                    $fieldName,
                                    var_export($fieldType->hookName, true),
                                    $this->buildMethodName($fieldType),
                                );
                            });
                            yield '}';

                            continue;
                        }

                        if ($fieldType instanceof FlattenedPropertyType) {
                            yield from $this->dumpFlattenedProperty(
                                $fieldName,
                                $fieldType,
                                $fieldType->throwsWhenNull()
                                || $this->constructsNarrowedClass($fieldType, $plansByFqcn),
                                $parentType->name(),
                                $isMutationData,
                                $generator,
                                $deprecatedFields[$fieldName] ?? null,
                            );

                            continue;
                        }

                        if ($fieldType instanceof ThrowWhenNullPropertyType) {
                            $wrappedType = $fieldType->getWrappedType();

                            yield from $generator->docComment(function () use (
                                $wrappedType,
                                $isMutationData,
                                $generator,
                                $deprecatedFields,
                                $fieldName,
                            ) {
                                if ($isMutationData) {
                                    yield '@api';
                                }

                                if ($this->getNakedType($wrappedType) instanceof SymfonyType\CollectionType) {
                                    yield sprintf('@var %s', TypeDumper::dump($wrappedType, $generator->import(...)));
                                }

                                yield from $this->dumpDeprecation($deprecatedFields[$fieldName] ?? null);
                            });
                            yield sprintf(
                                'public %s $%s {',
                                $this->dumpPHPType($wrappedType, $generator->import(...)),
                                $fieldName,
                            );
                            yield $generator->indent(function () use (
                                $wrappedType,
                                $fieldName,
                                $generator,
                                $parentType,
                                $deprecatedFields,
                            ) {
                                yield from $generator->docComment(function () use (
                                    $generator,
                                    $deprecatedFields,
                                    $fieldName,
                                ) {
                                    yield sprintf(
                                        '@throws %s',
                                        $generator->import($this->fullyQualified('NodeNotFoundException')),
                                    );
                                    yield from $this->dumpDeprecatedPropertyRead($deprecatedFields[$fieldName] ?? null);
                                });
                                yield from $generator->wrap(
                                    sprintf(
                                        'get => $this->%s ??= $this->data[%s] !== null ? ',
                                        $fieldName,
                                        var_export($fieldName, true),
                                    ),
                                    $this->typeInitializer->__invoke(
                                        $wrappedType,
                                        $generator,
                                        sprintf('$this->data[%s]', var_export($fieldName, true)),
                                    ),
                                    sprintf(
                                        ' : throw %s::create(%s, %s);',
                                        $generator->import($this->fullyQualified('NodeNotFoundException')),
                                        var_export($parentType->name(), true),
                                        var_export($fieldName, true),
                                    ),
                                );
                            });
                            yield '}';

                            continue;
                        }

                        // Check the payload shape to properly handle nullability
                        $payloadType = $payloadFieldTypes[$fieldName] ?? null;

                        // Use field type as the base, but incorporate nullability from payload if needed
                        $propertyType = $fieldType;

                        // If payload type is nullable but field type isn't, make property nullable
                        if (
                            $payloadType instanceof SymfonyType\NullableType
                            && !$fieldType instanceof SymfonyType\NullableType
                        ) {
                            $propertyType = SymfonyType::nullable($fieldType);
                        }

                        // For optional fields, the property must be nullable (since field might not exist)
                        if ($optional && !$propertyType instanceof SymfonyType\NullableType) {
                            $propertyType = SymfonyType::nullable($propertyType);
                        }

                        yield from $generator->docComment(function () use (
                            $plan,
                            $fieldName,
                            $propertyType,
                            $isMutationData,
                            $generator,
                            $deprecatedFields,
                        ) {
                            if ($isMutationData || $fieldName === '__typename' && $plan->markTypenameAsApi) {
                                yield '@api';
                            }

                            if ($this->getNakedType($propertyType) instanceof SymfonyType\CollectionType) {
                                yield sprintf('@var %s', TypeDumper::dump($propertyType, $generator->import(...)));
                            }

                            yield from $this->dumpDeprecation($deprecatedFields[$fieldName] ?? null);
                        });
                        yield sprintf(
                            'public %s $%s {',
                            $this->dumpPHPType($propertyType, $generator->import(...)),
                            $fieldName,
                        );

                        yield $generator->indent($generator->docComment(function () use (
                            $fieldType,
                            $plansByFqcn,
                            $generator,
                            $deprecatedFields,
                            $fieldName,
                        ) {
                            if ($this->constructsNarrowedClass($fieldType, $plansByFqcn)) {
                                yield sprintf(
                                    '@throws %s',
                                    $generator->import($this->fullyQualified('NodeNotFoundException')),
                                );
                            }

                            yield from $this->dumpDeprecatedPropertyRead($deprecatedFields[$fieldName] ?? null);
                        }));

                        yield $generator->indent(function () use (
                            $parentType,
                            $nakedFieldType,
                            $fieldType,
                            $generator,
                            $fieldName,
                            $requiredFieldsMap,
                            $optional,
                            $payloadFieldTypes,
                            $propertyType,
                            $plansByFqcn,
                        ) {
                            if ($nakedFieldType instanceof FragmentObjectType) {
                                // For interface/union parent types, we need special handling
                                if (!$parentType instanceof ObjectType) {
                                    // Check if we have required fields for this fragment
                                    $fragmentClassName = $nakedFieldType->getClassName();
                                    $requiredFields = $requiredFieldsMap[$fragmentClassName] ?? [];

                                    $construct = $this->initializeFragmentObject($nakedFieldType, $generator);

                                    // For fragments on interface/union types themselves
                                    if (
                                        $nakedFieldType->fragmentType instanceof InterfaceType
                                        || $nakedFieldType->fragmentType instanceof UnionType
                                    ) {
                                        yield sprintf(
                                            'get => $this->%s ??= in_array($this->data[\'__typename\'], [%s], true) ? %s : null;',
                                            $fieldName,
                                            $this->dumpPossibleTypesList($plansByFqcn, $nakedFieldType->getClassName()),
                                            $construct,
                                        );

                                        return;
                                    }

                                    // For fragments on concrete types when parent is interface/union
                                    // We need to check both typename and required fields
                                    if ($requiredFields === []) {
                                        // No required fields to check, only typename
                                        yield sprintf(
                                            'get => $this->%s ??= $this->data[\'__typename\'] === %s ? %s : null;',
                                            $fieldName,
                                            var_export($nakedFieldType->fragmentType->name(), true),
                                            $construct,
                                        );
                                    } else {
                                        // Generate verbose getter with field checks for PHPStan type safety
                                        yield 'get {';
                                        yield $generator->indent(static function () use (
                                            $fieldName,
                                            $nakedFieldType,
                                            $requiredFields,
                                            $construct,
                                        ) {
                                            yield sprintf('if (isset($this->%s)) {', $fieldName);
                                            yield '    return $this->' . $fieldName . ';';
                                            yield '}';
                                            yield '';
                                            yield sprintf('if ($this->data[\'__typename\'] !== %s) {', var_export(
                                                $nakedFieldType->fragmentType->name(),
                                                true,
                                            ));
                                            yield '    return $this->' . $fieldName . ' = null;';
                                            yield '}';

                                            // Check all required fields exist
                                            foreach ($requiredFields as $requiredField) {
                                                yield '';
                                                yield sprintf('if (! array_key_exists(%s, $this->data)) {', var_export(
                                                    $requiredField,
                                                    true,
                                                ));
                                                yield '    return $this->' . $fieldName . ' = null;';
                                                yield '}';
                                            }

                                            yield '';
                                            yield sprintf('return $this->%s = %s;', $fieldName, $construct);
                                        });
                                        yield '}';
                                    }

                                    return;
                                }

                                // For ObjectType parents, fragments don't need field checking
                                yield sprintf(
                                    'get => $this->%s ??= %s;',
                                    $fieldName,
                                    $this->initializeFragmentObject($nakedFieldType, $generator),
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
                                yield $generator->indent(function () use (
                                    $fieldName,
                                    $fieldType,
                                    $generator,
                                    $isPayloadNullable,
                                ) {
                                    yield sprintf('if (isset($this->%s)) {', $fieldName);
                                    yield '    return $this->' . $fieldName . ';';
                                    yield '}';
                                    yield '';
                                    yield sprintf('if (! array_key_exists(%s, $this->data)) {', var_export(
                                        $fieldName,
                                        true,
                                    ));
                                    yield '    return $this->' . $fieldName . ' = null;';
                                    yield '}';

                                    // Only check for null if the payload field is nullable
                                    if ($isPayloadNullable) {
                                        yield '';
                                        yield sprintf('if ($this->data[%s] === null) {', var_export($fieldName, true));
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
                                    sprintf('get => $this->%s ??= ', $fieldName),
                                    $this->typeInitializer->__invoke(
                                        $propertyType,
                                        $generator,
                                        sprintf('$this->data[%s]', var_export($fieldName, true)),
                                    ),
                                    ';',
                                );
                            } else {
                                yield from $generator->wrap(
                                    sprintf('get => $this->%s ??= ', $fieldName),
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

                        if (
                            $nakedFieldType instanceof FragmentObjectType
                            && $fieldType instanceof SymfonyType\NullableType
                        ) {
                            yield '';
                            yield from $generator->docComment(static function () use ($fieldName) {
                                yield '@api';
                                yield sprintf('@phpstan-assert-if-true !null $this->%s', $fieldName);
                            });
                            yield sprintf('public bool $is%s {', $nakedFieldType->fragmentName);
                            yield $generator->indent(function () use ($nakedFieldType, $plansByFqcn) {
                                if ($nakedFieldType->fragmentType instanceof ObjectType) {
                                    yield sprintf(
                                        'get => $this->is%s ??= $this->data[\'__typename\'] === %s;',
                                        $nakedFieldType->fragmentName,
                                        var_export($nakedFieldType->fragmentType->name(), true),
                                    );

                                    return;
                                }

                                yield sprintf(
                                    'get => $this->is%s ??= in_array($this->data[\'__typename\'], [%s], true);',
                                    $nakedFieldType->fragmentName,
                                    $this->dumpPossibleTypesList($plansByFqcn, $nakedFieldType->getClassName()),
                                );
                            });
                            yield '}';
                        }

                        if ($this->config->dumpOrThrowProperties && $propertyType instanceof SymfonyType\NullableType) {
                            $unwrappedType = $propertyType->getWrappedType();

                            yield '';
                            yield from $generator->docComment(function () use (
                                $unwrappedType,
                                $generator,
                                $deprecatedFields,
                                $fieldName,
                            ) {
                                if ($this->getNakedType($unwrappedType) instanceof SymfonyType\CollectionType) {
                                    yield sprintf('@var %s', TypeDumper::dump($unwrappedType, $generator->import(...)));
                                }

                                yield sprintf(
                                    '@throws %s',
                                    $generator->import($this->fullyQualified('NodeNotFoundException')),
                                );
                                yield from $this->dumpDeprecation($deprecatedFields[$fieldName] ?? null);
                            });
                            yield sprintf(
                                'public %s $%sOrThrow {',
                                $this->dumpPHPType($unwrappedType, $generator->import(...)),
                                $fieldName,
                            );
                            yield $generator->indent(function () use (
                                $parentType,
                                $generator,
                                $fieldName,
                                $deprecatedFields,
                            ) {
                                yield from $generator->docComment($this->dumpDeprecatedPropertyRead(
                                    $deprecatedFields[$fieldName] ?? null,
                                ));
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
                    yield from $generator->docComment(sprintf('@var %s', TypeDumper::dump(
                        $nodesType,
                        $generator->import(...),
                    )));
                    yield sprintf('public %s $nodes {', $this->dumpPHPType($nodesType, $generator->import(...)));

                    // Check if edges field has multi-field indexing
                    $isMultiFieldIndexedEdges = false;
                    $groupedEdges = null;

                    if ($fields instanceof ArrayShapeType) {
                        $edgesFieldType = $fields->getShape()['edges']['type'] ?? null;

                        if ($edgesFieldType instanceof IndexByCollectionType) {
                            $isMultiFieldIndexedEdges = $edgesFieldType->value instanceof IndexByCollectionType;
                        }

                        if ($edgesFieldType instanceof GroupByCollectionType) {
                            $groupedEdges = $edgesFieldType;
                        }
                    }

                    if ($groupedEdges !== null) {
                        // Grouped edges keep their groups: every group of edges becomes a group of nodes
                        yield $generator->indent(sprintf('get => %s;', $this->dumpGroupedNodes(
                            count($groupedEdges->keys),
                            '$this->edges',
                        )));
                    } elseif ($isMultiFieldIndexedEdges) {
                        // For multi-field indexed edges, we need nested loops
                        yield $generator->indent(static function () use ($generator) {
                            yield 'get {';
                            yield $generator->indent(static function () use ($generator) {
                                yield '$nodes = [];';
                                yield 'foreach ($this->edges as $edgeGroup) {';
                                yield $generator->indent(static function () use ($generator) {
                                    yield 'foreach ($edgeGroup as $edge) {';
                                    yield $generator->indent(static function () {
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
                        yield $generator->indent(sprintf('get => array_map(fn($edge) => $edge->node, $this->edges);'));
                    }

                    yield '}';
                }

                if ($isData) {
                    yield '';

                    yield from $generator->docComment('@var list<Error>');
                    yield 'public readonly array $errors;';
                }

                // Split the hooks this class touches into legacy (per-instance
                // __invoke) and batched (resolved once via a HookLoader).
                $legacyHooks = [];
                $batchedHooks = [];

                foreach (array_keys($plan->usedHooks) as $hookName) {
                    if ($this->config->hooks[$hookName]->batched) {
                        $batchedHooks[$hookName] = true;
                    } else {
                        $legacyHooks[$hookName] = true;
                    }
                }

                $usesBatched = $batchedHooks !== [];
                // The Data class always receives every hook instance (it needs
                // them to build the loaders); a nested class only needs $hooks
                // for legacy hooks. The Data class builds $loaders itself;
                // nested classes receive it.
                $hooksParam = $isData ? $plan->usedHooks : $legacyHooks;
                $needsHooksParam = $hooksParam !== [];
                $needsLoadersParam = !$isData && $usesBatched;
                $buildsLoaders = $isData && $usesBatched;
                $decodersParam = $plan->usedDecoders;

                if ($buildsLoaders) {
                    yield '';
                    yield from $generator->docComment(sprintf('@var %s', $this->dumpLoadersShape(
                        $batchedHooks,
                        $generator,
                    )));
                    yield 'private readonly array $loaders;';
                }

                $narrowing = $plan->narrowing;

                if ($narrowing !== null) {
                    // The constructor receives the payload of the interface or union and only
                    // stores it once it is known to be the narrowed type.
                    yield '';
                    yield from $generator->docComment(sprintf('@var %s', TypeDumper::dump(
                        $payloadShape,
                        $generator->import(...),
                    )));
                    yield 'private readonly array $data;';
                }

                yield '';
                yield from $generator->docComment(function () use (
                    $isData,
                    $generator,
                    $payloadShape,
                    $narrowing,
                    $hooksParam,
                    $needsHooksParam,
                    $needsLoadersParam,
                    $batchedHooks,
                    $decodersParam,
                ) {
                    yield sprintf('@param %s $data', TypeDumper::dump(
                        $narrowing->payloadShape ?? $payloadShape,
                        $generator->import(...),
                    ));

                    if ($isData) {
                        yield sprintf('@param %s $errors', TypeDumper::dump(
                            SymfonyType::list(SymfonyType::arrayShape([
                                'message' => SymfonyType::string(),
                                'code' => SymfonyType::string(),
                                'debugMessage' => [
                                    'type' => SymfonyType::string(),
                                    'optional' => true,
                                ],
                            ], sealed: false)),
                            $generator->import(...),
                        ));
                    }

                    if ($needsHooksParam) {
                        yield sprintf('@param %s $hooks', TypeDumper::dump(
                            $this->buildHooksShape($hooksParam),
                            $generator->import(...),
                        ));
                    }

                    if ($needsLoadersParam) {
                        yield sprintf('@param %s $loaders', $this->dumpLoadersShape($batchedHooks, $generator));
                    }

                    if ($decodersParam !== []) {
                        yield sprintf('@param %s $decoders', TypeDumper::dump(
                            $this->buildDecodersShape($decodersParam),
                            $generator->import(...),
                        ));
                    }

                    if ($narrowing !== null) {
                        yield sprintf('@throws %s', $generator->import($this->fullyQualified('NodeNotFoundException')));
                    }
                });
                yield 'public function __construct(';
                yield $generator->indent(function () use (
                    $generator,
                    $payloadShape,
                    $narrowing,
                    $isData,
                    $needsHooksParam,
                    $needsLoadersParam,
                    $decodersParam,
                ) {
                    yield sprintf(
                        $narrowing !== null ? '%s $data,' : 'private readonly %s $data,',
                        $this->dumpPHPType($payloadShape, $generator->import(...)),
                    );

                    if ($isData) {
                        yield 'array $errors,';
                    }

                    if ($needsHooksParam) {
                        yield 'private readonly array $hooks,';
                    }

                    if ($needsLoadersParam) {
                        yield 'private readonly array $loaders,';
                    }

                    if ($decodersParam !== []) {
                        yield 'private readonly array $decoders,';
                    }
                });

                if ($isData) {
                    yield ') {';
                    yield $generator->indent(function () use ($generator, $buildsLoaders, $batchedHooks) {
                        yield '$this->errors = array_map(fn(array $error) => new Error($error), $errors);';

                        if ($buildsLoaders) {
                            yield '';
                            yield '$this->loaders = [';
                            yield $generator->indent(function () use ($generator, $batchedHooks) {
                                foreach (array_keys($batchedHooks) as $hookName) {
                                    yield sprintf(
                                        '%s => new %s(',
                                        var_export($hookName, true),
                                        $generator->import($this->fullyQualified('HookLoader')),
                                    );
                                    yield $generator->indent(function () use ($hookName) {
                                        yield sprintf('$this->%s(...),', $this->collectMethodName($hookName));
                                        yield sprintf('$this->hooks[%s]->__invoke(...),', var_export($hookName, true));
                                    });
                                    yield '),';
                                }
                            });
                            yield '];';
                        }
                    });
                    yield '}';
                } elseif ($narrowing !== null) {
                    yield ') {';
                    yield $generator->indent($this->dumpNarrowingCheck($narrowing, $generator));
                    yield '}';
                } else {
                    yield ') {}';
                }

                yield from $this->dumpHookBuildMethods($plan, $plansByFqcn, $generator);
                yield from $this->dumpCollectMethods($plan, $plansByFqcn, $generator);
            });
            yield '}';
        });
    }
}
