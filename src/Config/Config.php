<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use Closure;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use InvalidArgumentException;
use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\TypeInitializer;
use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Webmozart\Assert\Assert;

final readonly class Config
{
    /**
     * @param array<string, array{Type, Type}> $scalars
     * @param array<string, Type> $inputObjectTypes
     * @param array<string, array{Type, Type}> $objectTypes
     * @param array<string, Type> $enumTypes
     * @param list<string> $ignoreTypes
     * @param list<TypeInitializer\TypeInitializer> $typeInitializers
     * @param null|object|(Closure(): object) $introspectionClient
     * @param list<string> $inlineProcessingDirectories
     * @param list<string> $twigProcessingDirectories
     * @param array<string, HookDefinition> $hooks
     * @param list<OperationArgument> $operationArguments
     */
    private function __construct(
        public Schema | string $schema,
        public string $projectDir,
        public string $outputDir,
        public string $namespace,
        public string $client,
        public ?string $queriesDir = null,
        public bool $dumpOrThrowMethods = false,
        public bool $dumpOrThrowProperties = false,
        public bool $dumpDefinition = false,
        public bool $useNodeNameForEdgeNodes = false,
        public array $scalars = [],
        public array $inputObjectTypes = [],
        public array $objectTypes = [],
        public array $enumTypes = [],
        public array $ignoreTypes = [],
        public array $typeInitializers = [],
        public bool $useConnectionNameForConnections = false,
        public bool $useEdgeNameForEdges = false,
        public bool $addNodesOnConnections = false,
        public bool $addSymfonyExcludeAttribute = false,
        public bool $addGeneratedAttribute = false,
        public bool $indexByDirective = false,
        public bool $throwWhenNullDirective = false,
        public bool $addUnknownCaseToEnums = false,
        public bool $dumpEnumIsMethods = false,
        public ?object $introspectionClient = null,
        public array $inlineProcessingDirectories = [],
        public array $twigProcessingDirectories = [],
        public bool $formatOperationFiles = false,
        public array $hooks = [],
        public bool $symfonyAutowireHooks = false,
        public array $operationArguments = [],
    ) {}

    public static function create(
        Schema | string $schema,
        string $projectDir,
        string $outputDir,
        string $namespace,
        string $client,
    ) : self {
        return new self(
            schema: $schema,
            projectDir: $projectDir,
            outputDir: $outputDir,
            namespace: $namespace,
            client: $client,
        );
    }

    public function enableDumpOrThrowMethods() : self
    {
        return clone ($this, [
            'dumpOrThrowMethods' => true,
        ]);
    }

    public function enableDumpOrThrowProperties() : self
    {
        return clone ($this, [
            'dumpOrThrowProperties' => true,
        ]);
    }

    public function enableDumpDefinition() : self
    {
        return clone ($this, [
            'dumpDefinition' => true,
        ]);
    }

    public function enableUseNodeNameForEdgeNodes() : self
    {
        return clone ($this, [
            'useNodeNameForEdgeNodes' => true,
        ]);
    }

    public function enableUseConnectionNameForConnections() : self
    {
        return clone ($this, [
            'useConnectionNameForConnections' => true,
        ]);
    }

    public function enableUseEdgeNameForEdges() : self
    {
        return clone ($this, [
            'useEdgeNameForEdges' => true,
        ]);
    }

    public function enableAddNodesOnConnections() : self
    {
        return clone ($this, [
            'addNodesOnConnections' => true,
        ]);
    }

    public function enableSymfonyExcludeAttribute() : self
    {
        return clone ($this, [
            'addSymfonyExcludeAttribute' => true,
        ]);
    }

    public function enableGeneratedAttribute() : self
    {
        return clone ($this, [
            'addGeneratedAttribute' => true,
        ]);
    }

    public function enableIndexByDirective() : self
    {
        return clone ($this, [
            'indexByDirective' => true,
        ]);
    }

    public function enableThrowWhenNullDirective() : self
    {
        return clone ($this, [
            'throwWhenNullDirective' => true,
        ]);
    }

    public function enableAddUnknownCaseToEnums() : self
    {
        return clone ($this, [
            'addUnknownCaseToEnums' => true,
        ]);
    }

    public function enableDumpEnumIsMethods() : self
    {
        return clone ($this, [
            'dumpEnumIsMethods' => true,
        ]);
    }

    public function enableFormatOperationFiles() : self
    {
        return clone ($this, [
            'formatOperationFiles' => true,
        ]);
    }

    /**
     * Emit Symfony `#[Autowire([...])]` on the generated query class's `$hooks`
     * constructor argument so the DI container can inject each hook service by class name.
     */
    public function enableSymfonyAutowireHooks() : self
    {
        return clone ($this, [
            'symfonyAutowireHooks' => true,
        ]);
    }

    public function withScalar(string $name, Type $type, ?Type $payloadType = null) : self
    {
        $scalars = $this->scalars;
        $scalars[$name] = [$type, $payloadType ?? $type];

        return clone ($this, [
            'scalars' => $scalars,
        ]);
    }

    public function withInputObjectType(string $name, Type $type) : self
    {
        $inputObjectTypes = $this->inputObjectTypes;
        $inputObjectTypes[$name] = $type;

        return clone ($this, [
            'inputObjectTypes' => $inputObjectTypes,
        ]);
    }

    public function withObjectType(string $name, Type $payloadShape, Type $payloadType) : self
    {
        $objectTypes = $this->objectTypes;
        $objectTypes[$name] = [$payloadShape, $payloadType];

        return clone ($this, [
            'objectTypes' => $objectTypes,
        ]);
    }

    public function withEnumType(string $name, Type $type) : self
    {
        $enumTypes = $this->enumTypes;
        $enumTypes[$name] = $type;

        return clone ($this, [
            'enumTypes' => $enumTypes,
        ]);
    }

    public function withIgnoreType(string $type) : self
    {
        $ignoreTypes = $this->ignoreTypes;
        $ignoreTypes[] = $type;

        return clone ($this, [
            'ignoreTypes' => $ignoreTypes,
        ]);
    }

    public function withTypeInitializer(TypeInitializer\TypeInitializer $typeInitializer) : self
    {
        $typeInitializers = $this->typeInitializers;
        $typeInitializers[] = $typeInitializer;

        return clone ($this, [
            'typeInitializers' => $typeInitializers,
        ]);
    }

    public function withIntrospectionClient(object $client) : self
    {
        return clone ($this, [
            'introspectionClient' => $client,
        ]);
    }

    public function withQueriesDir(string $queriesDir) : self
    {
        return clone ($this, [
            'queriesDir' => $queriesDir,
        ]);
    }

    public function withInlineProcessingDirectory(string $directory, string ...$directories) : self
    {
        return clone ($this, [
            'inlineProcessingDirectories' => array_merge($this->inlineProcessingDirectories, [$directory], array_values($directories)),
        ]);
    }

    public function withTwigProcessingDirectory(string $directory, string ...$directories) : self
    {
        return clone ($this, [
            'twigProcessingDirectories' => array_merge($this->twigProcessingDirectories, [$directory], array_values($directories)),
        ]);
    }

    /**
     * Register a hook. The class must be invokable (`__invoke`) and must carry
     * `#[Hook(name: '...', requires: self::REQUIRES)]`.
     *
     * `requires` is a named GraphQL fragment declaring the data the hook needs; the
     * generator injects that selection into queries that use the hook and hands the
     * hook a typed object built from it. The return type is inferred from `__invoke`.
     *
     * A legacy hook is invoked once per object instance: `__invoke(DataClass $input): V`.
     * A batched hook (`#[Hook(..., batched: true)]`) is invoked exactly once per
     * operation: `__invoke(array<int, DataClass> $inputs): iterable<int, V>`, echoing the
     * integer keys it was given.
     *
     * @param class-string $class
     * @throws InvalidArgumentException
     * @throws \Webmozart\Assert\InvalidArgumentException
     * @throws ReflectionException
     * @throws JsonException
     */
    public function withHook(string $class) : self
    {
        Assert::classExists($class, sprintf('Hook class "%s" does not exist.', $class));
        Assert::methodExists($class, '__invoke', sprintf('Hook class "%s" must be invokable (define __invoke).', $class));

        $attributes = new ReflectionClass($class)->getAttributes(Hook::class);

        Assert::notEmpty($attributes, sprintf(
            'Hook class "%s" must carry a #[Hook(name: "...")] attribute.',
            $class,
        ));

        $hook = $attributes[0]->newInstance();
        $hookName = $hook->name;
        $batched = $hook->batched;

        Assert::regex($hookName, '/^[a-zA-Z_][a-zA-Z0-9_]*$/', sprintf(
            'Hook name "%s" (on %s) must be a valid PHP identifier.',
            $hookName,
            $class,
        ));

        try {
            $document = Parser::parse($hook->requires);
        } catch (SyntaxError $exception) {
            throw new InvalidArgumentException(
                sprintf('The `requires` of hook "%s" (%s) is not valid GraphQL.', $hookName, $class),
                previous: $exception,
            );
        }

        Assert::count($document->definitions, 1, sprintf(
            'The `requires` of hook "%s" (%s) must contain exactly one named fragment.',
            $hookName,
            $class,
        ));

        $fragment = $document->definitions[0];

        Assert::isInstanceOf($fragment, FragmentDefinitionNode::class, sprintf(
            'The `requires` of hook "%s" (%s) must be a named fragment '
            . '(fragment Name on Type { ... }).',
            $hookName,
            $class,
        ));

        $requiresClassName = $fragment->name->value;

        Assert::regex($requiresClassName, '/^[A-Z][a-zA-Z0-9_]*$/', sprintf(
            'The `requires` fragment name "%s" of hook "%s" must be a valid PHP class name.',
            $requiresClassName,
            $hookName,
        ));

        $requiresTypeCondition = $fragment->typeCondition->name->value;
        $requiresFqcn = $this->namespace . '\\Hook\\' . $requiresClassName;

        $method = new ReflectionMethod($class, '__invoke');

        Assert::keyNotExists($this->hooks, $hookName, sprintf(
            'Hook "%s" is already registered (at %s).',
            $hookName,
            $this->hooks[$hookName]->class ?? '?',
        ));

        try {
            $returnType = TypeResolver::create()->resolve($method);
        } catch (UnsupportedException $exception) {
            throw new InvalidArgumentException(
                sprintf(
                    'Could not infer return type for hook "%s" from %s::__invoke(). Declare an explicit return type.',
                    $hookName,
                    $class,
                ),
                previous: $exception,
            );
        }

        if ($batched) {
            $parameters = $method->getParameters();

            if (count($parameters) !== 1 || ! $this->isArrayHookParameter($parameters[0])) {
                throw new InvalidArgumentException(sprintf(
                    'Batched hook "%s" (%s::__invoke) must accept exactly one array argument: '
                    . 'public function __invoke(array $inputs): iterable. Each entry of $inputs is '
                    . 'one occurrence\'s %s, integer-keyed by the library.',
                    $hookName,
                    $class,
                    $requiresClassName,
                ));
            }

            if ( ! $returnType instanceof Type\CollectionType) {
                throw new InvalidArgumentException(sprintf(
                    'Batched hook "%s" (%s::__invoke) must return an iterable; declare '
                    . '@return iterable<int, V> so the value type can be inferred.',
                    $hookName,
                    $class,
                ));
            }

            $returnType = $returnType->getCollectionValueType();
        }

        $hooks = $this->hooks;
        $hooks[$hookName] = new HookDefinition(
            $hookName,
            $class,
            $returnType,
            $batched,
            $fragment,
            $requiresClassName,
            $requiresFqcn,
            $requiresTypeCondition,
        );

        return clone ($this, [
            'hooks' => $hooks,
        ]);
    }

    /**
     * Register an extra parameter to inject into generated operation methods.
     *
     * The parameter is prepended to `execute()`/`executeOrThrow()` and forwarded
     * positionally to the client's `graphql()` call (after the operation name).
     *
     * When `$directive` is given, the parameter only applies to operations carrying
     * that directive (e.g. `mutation Foo @requiresActor`). When `$directive` is null,
     * it applies to every operation whose type is listed in `$operations`.
     *
     * `$operations` restricts which operation types the argument may target. Leave it
     * empty (the default) to allow any operation type.
     *
     * @param list<'query'|'mutation'> $operations
     * @throws \Webmozart\Assert\InvalidArgumentException
     */
    public function withOperationArgument(
        string $name,
        Type $type,
        ?string $directive = null,
        array $operations = [],
    ) : self {
        Assert::regex($name, '/^[a-zA-Z_][a-zA-Z0-9_]*$/', sprintf(
            'Operation argument name "%s" must be a valid PHP identifier.',
            $name,
        ));

        if ($directive !== null) {
            Assert::regex($directive, '/^[a-zA-Z_][a-zA-Z0-9_]*$/', sprintf(
                'Operation argument directive "%s" must be a valid GraphQL directive name.',
                $directive,
            ));
        }

        $operationArguments = $this->operationArguments;
        $operationArguments[] = new OperationArgument($name, $type, $directive, $operations);

        return clone ($this, [
            'operationArguments' => $operationArguments,
        ]);
    }

    /**
     * A batched hook's single parameter must be `array` (or `iterable`/untyped) — it
     * receives the whole batch of input tuples.
     */
    private function isArrayHookParameter(ReflectionParameter $parameter) : bool
    {
        $type = $parameter->getType();

        if ($type === null) {
            return true;
        }

        return $type instanceof ReflectionNamedType
            && in_array($type->getName(), ['array', 'iterable'], true);
    }
}
