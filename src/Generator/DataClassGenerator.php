<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\CodeGenerator\Group;
use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQL\AST\Printer;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Source\GraphQLFileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\TwigFileSource;
use Ruudk\GraphQLCodeGenerator\Type\ArrayTupleType;
use Ruudk\GraphQLCodeGenerator\Type\FragmentObjectType;
use Ruudk\GraphQLCodeGenerator\Type\HookPropertyType;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Ruudk\GraphQLCodeGenerator\Type\StringLiteralType;
use Ruudk\GraphQLCodeGenerator\Type\ThrowWhenNullPropertyType;
use Ruudk\GraphQLCodeGenerator\Type\TypeDumper;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Symfony\Component\TypeInfo\Type\ArrayShapeType;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final class DataClassGenerator extends AbstractGenerator
{
    public function __construct(
        Config $config,
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
    private function initializeFragmentObject(FragmentObjectType $type, CodeGenerator $generator) : string
    {
        $result = $this->typeInitializer->__invoke($type, $generator, '$this->data');
        Assert::string($result);

        return $result;
    }

    /**
     * Walks a dotted `@hook` input path through the current class's field shape
     * (and the nested classes referenced along the way), emitting a property-
     * chain accessor like `$this->creator->id`. Nullable intermediates get
     * promoted to `?->` so the chain's static type stays accurate. `$base` is
     * the root expression the chain hangs off — `$this` for a getter, or a
     * loop/instance variable inside an inlined collect walk.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     */
    private function buildHookInputAccessor(string $path, SymfonyType $fields, array $plansByFqcn, string $base = '$this') : string
    {
        $segments = explode('.', $path);
        $accessor = $base;
        $chainNullable = false;
        $shape = $this->unwrapShape($fields);

        $last = count($segments) - 1;

        foreach ($segments as $i => $segment) {
            $shapeArray = $shape->getShape();

            Assert::keyExists($shapeArray, $segment, sprintf(
                'Hook input path "%s" references unknown field "%s".',
                $path,
                $segment,
            ));

            $segmentType = $shapeArray[$segment]['type'];

            $accessor .= ($chainNullable ? '?->' : '->') . $segment;

            if ($i === $last) {
                break;
            }

            $isNullable = $segmentType instanceof SymfonyType\NullableType;
            $naked = $isNullable ? $segmentType->getWrappedType() : $segmentType;

            Assert::isInstanceOf($naked, SymfonyType\ObjectType::class, sprintf(
                'Hook input path "%s" cannot descend through non-object segment "%s".',
                $path,
                $segment,
            ));

            $nextFqcn = $naked->getClassName();

            Assert::keyExists($plansByFqcn, $nextFqcn, sprintf(
                'Hook input path "%s" references class "%s" that has no generated plan.',
                $path,
                $nextFqcn,
            ));

            $shape = $this->unwrapShape($plansByFqcn[$nextFqcn]->fields);
            $chainNullable = $chainNullable || $isNullable;
        }

        return $accessor;
    }

    /**
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     */
    private function dumpPossibleTypesList(array $plansByFqcn, string $fqcn) : string
    {
        Assert::keyExists($plansByFqcn, $fqcn);

        return implode(
            ', ',
            array_map(
                fn(string $type) => var_export($type, true),
                $plansByFqcn[$fqcn]->possibleTypes,
            ),
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function unwrapShape(SymfonyType $type) : ArrayShapeType
    {
        if ($type instanceof SymfonyType\NullableType) {
            $type = $type->getWrappedType();
        }

        Assert::isInstanceOf($type, ArrayShapeType::class);

        return $type;
    }

    /**
     * Name of the generated method that walks the typed object graph collecting
     * a batched hook's input tuples. Hook names are validated as PHP identifiers
     * by `Config::withHook()`.
     */
    private function collectMethodName(string $hookName) : string
    {
        return 'collectHook' . ucfirst($hookName) . 'Inputs';
    }

    /**
     * PHPDoc `array{hookName: HookLoader<TInput, V>, ...}` shape for the
     * `$loaders` argument/property. `TInput` is the hook's input tuple shape
     * and `V` its (unwrapped) return value type.
     *
     * @param array<string, true> $batchedHooks
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     */
    private function dumpLoadersShape(array $batchedHooks, array $plansByFqcn, CodeGenerator $generator) : string
    {
        $hookLoader = $generator->import($this->fullyQualified('HookLoader'));
        $entries = [];

        foreach (array_keys($batchedHooks) as $name) {
            $entries[] = sprintf(
                '%s: %s<%s, %s>',
                $name,
                $hookLoader,
                TypeDumper::dump($this->resolveHookInputTuple($name, $plansByFqcn), $generator->import(...), 1),
                TypeDumper::dump($this->config->hooks[$name]->returnType, $generator->import(...), 1),
            );
        }

        // Unsealed: nested classes receive `$this->loaders` from the Data class,
        // which holds every batched hook in the operation, not just the subset a
        // single nested class consumes. Extras are typed wide; HookLoader's
        // covariant template params let any specific instantiation fit here.
        $entries[] = sprintf('...<string, %s<array{...}, mixed>>', $hookLoader);

        return sprintf("array{\n    %s,\n}", implode(",\n    ", $entries));
    }

    /**
     * Build the input tuple type `array{T1, T2, ...}` for a batched hook from
     * the leaf types of its `@hook(input: [...])` paths, located at the first
     * site that uses the hook. Always resolves: the caller only asks for hooks
     * that are in a class's `usedHooks`, which means a `@hook` field exists.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     */
    private function resolveHookInputTuple(string $hookName, array $plansByFqcn) : ArrayTupleType
    {
        foreach ($plansByFqcn as $plan) {
            $fields = $plan->fields;

            if ($fields instanceof SymfonyType\NullableType) {
                $fields = $fields->getWrappedType();
            }

            if ( ! $fields instanceof ArrayShapeType) {
                continue;
            }

            foreach ($fields->getShape() as $fieldValue) {
                $fieldType = $fieldValue['type'];

                if ( ! $fieldType instanceof HookPropertyType || $fieldType->hookName !== $hookName) {
                    continue;
                }

                $leaves = [];

                foreach ($fieldType->inputPaths as $path) {
                    $leaves[] = $this->resolveHookInputLeafType($path, $plan->fields, $plansByFqcn);
                }

                return new ArrayTupleType($leaves);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'No @hook field found for hook "%s"; cannot resolve its input tuple.',
            $hookName,
        ));
    }

    /**
     * Resolve the PHP type a dotted `@hook` input path ultimately reads — the
     * companion of `buildHookInputAccessor`, which builds the accessor string.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     */
    private function resolveHookInputLeafType(string $path, SymfonyType $fields, array $plansByFqcn) : SymfonyType
    {
        $segments = explode('.', $path);
        $shape = $this->unwrapShape($fields);
        $last = count($segments) - 1;

        foreach ($segments as $i => $segment) {
            $shapeArray = $shape->getShape();

            Assert::keyExists($shapeArray, $segment, sprintf(
                'Hook input path "%s" references unknown field "%s".',
                $path,
                $segment,
            ));

            $segmentType = $shapeArray[$segment]['type'];

            if ($i === $last) {
                return $segmentType;
            }

            $naked = $segmentType instanceof SymfonyType\NullableType
                ? $segmentType->getWrappedType()
                : $segmentType;

            Assert::isInstanceOf($naked, SymfonyType\ObjectType::class, sprintf(
                'Hook input path "%s" cannot descend through non-object segment "%s".',
                $path,
                $segment,
            ));

            $nextFqcn = $naked->getClassName();

            Assert::keyExists($plansByFqcn, $nextFqcn, sprintf(
                'Hook input path "%s" references class "%s" that has no generated plan.',
                $path,
                $nextFqcn,
            ));

            $shape = $this->unwrapShape($plansByFqcn[$nextFqcn]->fields);
        }

        throw new InvalidArgumentException(sprintf('Hook input path "%s" is empty.', $path));
    }

    /**
     * Replicates the getter's property-type computation: a field's PHP property
     * type is its field type made nullable when the payload is nullable or the
     * field is optional. Used to emit correctly null-guarded collect traversals.
     */
    private function effectivePropertyType(SymfonyType $fieldType, ?SymfonyType $payloadType, bool $optional) : SymfonyType
    {
        $propertyType = $fieldType;

        if ($payloadType instanceof SymfonyType\NullableType && ! ($fieldType instanceof SymfonyType\NullableType)) {
            $propertyType = SymfonyType::nullable($fieldType);
        }

        if ($optional && ! ($propertyType instanceof SymfonyType\NullableType)) {
            $propertyType = SymfonyType::nullable($propertyType);
        }

        return $propertyType;
    }

    /**
     * Peel nullable/collection/throw-when-null wrappers; return the FQCN of the
     * nested ObjectType if that is what remains. Mirrors `Planner::unwrapToObjectType`
     * so the collect traversal reaches exactly the classes hook propagation marked.
     */
    private function unwrapToObjectClassName(SymfonyType $type) : ?string
    {
        while (true) {
            if ($type instanceof ThrowWhenNullPropertyType) {
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
    private function emitCollectDescent(string $accessor, SymfonyType $type, DataClassPlan $childPlan, string $hookName, array $plansByFqcn, CodeGenerator $generator, int $depth) : iterable
    {
        if ($type instanceof ThrowWhenNullPropertyType) {
            yield from $this->emitCollectDescent($accessor, $type->getWrappedType(), $childPlan, $hookName, $plansByFqcn, $generator, $depth);

            return;
        }

        if ($type instanceof SymfonyType\NullableType) {
            yield sprintf('if (%s !== null) {', $accessor);
            yield $generator->indent(function () use ($accessor, $type, $childPlan, $hookName, $plansByFqcn, $generator, $depth) {
                yield from $this->emitCollectDescent($accessor, $type->getWrappedType(), $childPlan, $hookName, $plansByFqcn, $generator, $depth);
            });
            yield '}';

            return;
        }

        if ($type instanceof SymfonyType\CollectionType) {
            $item = '$item' . ($depth > 0 ? (string) $depth : '');
            yield sprintf('foreach (%s as %s) {', $accessor, $item);
            yield $generator->indent(function () use ($item, $type, $childPlan, $hookName, $plansByFqcn, $generator, $depth) {
                yield from $this->emitCollectDescent($item, $type->getCollectionValueType(), $childPlan, $hookName, $plansByFqcn, $generator, $depth + 1);
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
    private function emitCollectClassBody(string $accessor, DataClassPlan $plan, string $hookName, array $plansByFqcn, CodeGenerator $generator, int $depth) : iterable
    {
        $fields = $plan->fields;

        if ($fields instanceof SymfonyType\NullableType) {
            $fields = $fields->getWrappedType();
        }

        if ( ! $fields instanceof ArrayShapeType) {
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
                    $args = [];

                    foreach ($fieldType->inputPaths as $path) {
                        $args[] = $this->buildHookInputAccessor($path, $fields, $plansByFqcn, $accessor);
                    }

                    yield sprintf('yield [%s, [%s]];', $accessor, implode(', ', $args));
                }

                continue;
            }

            if ($fieldType instanceof ThrowWhenNullPropertyType) {
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

            if ($leafFqcn === null
                || ! isset($plansByFqcn[$leafFqcn])
                || ! isset($plansByFqcn[$leafFqcn]->usedHooks[$hookName])
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
     * the typed object graph, yielding `[$owner, $inputTuple]` pairs for the
     * `HookLoader` to batch. Keeping the walk on `Data` lets it stay private
     * and leaves nested classes free of generated traversal methods.
     *
     * @param array<string, DataClassPlan> $plansByFqcn
     * @throws InvalidArgumentException
     * @return iterable<string|Group>
     */
    private function dumpCollectMethods(DataClassPlan $plan, array $plansByFqcn, CodeGenerator $generator) : iterable
    {
        if ( ! $plan->isData) {
            return;
        }

        foreach (array_keys($plan->usedHooks) as $hookName) {
            if ( ! $this->config->hooks[$hookName]->batched) {
                continue;
            }

            yield '';
            yield from $generator->docComment(sprintf(
                '@return iterable<array{object, %s}>',
                TypeDumper::dump($this->resolveHookInputTuple($hookName, $plansByFqcn), $generator->import(...)),
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
     * @param array<string, DataClassPlan> $plansByFqcn
     */
    public function generate(DataClassPlan $plan, array $plansByFqcn = []) : string
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
        $isMutationData = $isData
            && $definitionNode instanceof OperationDefinitionNode
            && $definitionNode->operation === 'mutation';

        /**
         * @var array<string, list<string>>
         */
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

        return $generator->dumpFile(function () use ($plan, $definitionNode, $parentType, $nodesType, $fqcn, $payloadShape, $isData, $isMutationData, $fields, $generator, $inlineFragmentRequiredFields, $plansByFqcn) {
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

                    yield sprintf('source: %s', $generator->dumpClassReference($plan->source->class));
                    yield 'restricted: true';
                    yield 'restrictInstantiation: true';
                });
            }

            yield sprintf('final class %s', $generator->import($fqcn));
            yield '{';

            $requiredFieldsMap = $inlineFragmentRequiredFields;

            yield $generator->indent(
                function () use ($plan, $parentType, $nodesType, $fields, $isData, $isMutationData, $payloadShape, $generator, $requiredFieldsMap, $plansByFqcn) {
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
                            // at access time by invoking the user-supplied hook (an invokable
                            // class) with positional arguments in the order declared by the
                            // directive.
                            if ($fieldType instanceof HookPropertyType) {
                                $wrappedReturnType = $fieldType->getWrappedType();

                                yield from $generator->docComment(function () use ($wrappedReturnType, $isMutationData, $generator) {
                                    if ($isMutationData) {
                                        yield '@api';
                                    }

                                    if ($this->getNakedType($wrappedReturnType) instanceof SymfonyType\CollectionType) {
                                        yield sprintf(
                                            '@var %s',
                                            TypeDumper::dump($wrappedReturnType, $generator->import(...)),
                                        );
                                    }
                                });
                                yield sprintf(
                                    'public %s $%s {',
                                    $this->dumpPHPType($fieldType, $generator->import(...)),
                                    $fieldName,
                                );
                                yield $generator->indent(function () use ($fieldType, $fieldName, $fields, $plansByFqcn, $generator) {
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

                                    $args = [];

                                    foreach ($fieldType->inputPaths as $path) {
                                        $args[] = $this->buildHookInputAccessor($path, $fields, $plansByFqcn);
                                    }

                                    yield from $generator->wrap(
                                        sprintf('get => $this->%s ??= ', $fieldName),
                                        $generator->dumpCall(
                                            sprintf('$this->hooks[%s]', var_export($fieldType->hookName, true)),
                                            '__invoke',
                                            $args,
                                        ),
                                        ';',
                                    );
                                });
                                yield '}';

                                continue;
                            }

                            if ($fieldType instanceof ThrowWhenNullPropertyType) {
                                $wrappedType = $fieldType->getWrappedType();

                                yield from $generator->docComment(function () use ($wrappedType, $isMutationData, $generator) {
                                    if ($isMutationData) {
                                        yield '@api';
                                    }

                                    if ($this->getNakedType($wrappedType) instanceof SymfonyType\CollectionType) {
                                        yield sprintf(
                                            '@var %s',
                                            TypeDumper::dump($wrappedType, $generator->import(...)),
                                        );
                                    }
                                });
                                yield sprintf(
                                    'public %s $%s {',
                                    $this->dumpPHPType($wrappedType, $generator->import(...)),
                                    $fieldName,
                                );
                                yield $generator->indent(function () use ($wrappedType, $fieldName, $generator, $parentType) {
                                    yield from $generator->docComment(function () use ($generator) {
                                        yield sprintf('@throws %s', $generator->import($this->fullyQualified('NodeNotFoundException')));
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
                            if ($payloadType instanceof SymfonyType\NullableType && ! ($fieldType instanceof SymfonyType\NullableType)) {
                                $propertyType = SymfonyType::nullable($fieldType);
                            }

                            // For optional fields, the property must be nullable (since field might not exist)
                            if ($optional && ! ($propertyType instanceof SymfonyType\NullableType)) {
                                $propertyType = SymfonyType::nullable($propertyType);
                            }

                            yield from $generator->docComment(function () use ($plan, $fieldName, $propertyType, $isMutationData, $generator) {
                                if ($isMutationData || ($fieldName === '__typename' && $plan->markTypenameAsApi)) {
                                    yield '@api';
                                }

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
                            yield $generator->indent(function () use ($parentType, $nakedFieldType, $fieldType, $generator, $fieldName, $requiredFieldsMap, $optional, $payloadFieldTypes, $propertyType, $plansByFqcn) {
                                if ($nakedFieldType instanceof FragmentObjectType) {
                                    // For interface/union parent types, we need special handling
                                    if ( ! $parentType instanceof ObjectType) {
                                        // Check if we have required fields for this fragment
                                        $fragmentClassName = $nakedFieldType->getClassName();
                                        /**
                                         * @var list<string> $requiredFields
                                         */
                                        $requiredFields = $requiredFieldsMap[$fragmentClassName] ?? [];

                                        $construct = $this->initializeFragmentObject($nakedFieldType, $generator);

                                        // For fragments on interface/union types themselves
                                        if ($nakedFieldType->fragmentType instanceof InterfaceType || $nakedFieldType->fragmentType instanceof UnionType) {
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
                                            yield $generator->indent(function () use ($fieldName, $nakedFieldType, $requiredFields, $construct) {
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
                                                    'return $this->%s = %s;',
                                                    $fieldName,
                                                    $construct,
                                                );
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
                                yield from $generator->docComment(function () use ($fieldName) {
                                    yield '@api';
                                    yield sprintf('@phpstan-assert-if-true !null $this->%s', $fieldName);
                                });
                                yield sprintf(
                                    'public bool $is%s {',
                                    $nakedFieldType->fragmentName,
                                );
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
                    $needsLoadersParam = ! $isData && $usesBatched;
                    $buildsLoaders = $isData && $usesBatched;

                    if ($buildsLoaders) {
                        yield '';
                        yield from $generator->docComment(sprintf(
                            '@var %s',
                            $this->dumpLoadersShape($batchedHooks, $plansByFqcn, $generator),
                        ));
                        yield 'private readonly array $loaders;';
                    }

                    yield '';
                    yield from $generator->docComment(function () use ($isData, $generator, $payloadShape, $hooksParam, $needsHooksParam, $needsLoadersParam, $batchedHooks, $plansByFqcn) {
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
                                ], sealed: false)), $generator->import(...)),
                            );
                        }

                        if ($needsHooksParam) {
                            yield sprintf(
                                '@param %s $hooks',
                                TypeDumper::dump($this->buildHooksShape($hooksParam), $generator->import(...)),
                            );
                        }

                        if ($needsLoadersParam) {
                            yield sprintf(
                                '@param %s $loaders',
                                $this->dumpLoadersShape($batchedHooks, $plansByFqcn, $generator),
                            );
                        }
                    });
                    yield 'public function __construct(';
                    yield $generator->indent(function () use ($generator, $payloadShape, $isData, $needsHooksParam, $needsLoadersParam) {
                        yield sprintf(
                            'private readonly %s $data,',
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
                    } else {
                        yield ') {}';
                    }

                    yield from $this->dumpCollectMethods($plan, $plansByFqcn, $generator);
                },
            );
            yield '}';
        });
    }
}
