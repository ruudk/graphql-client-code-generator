<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Closure;
use GraphQL\Type\Schema;
use ReflectionClass;
use Symfony\Component\TypeInfo\Type;

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
     */
    private function __construct(
        public Schema | string $schema,
        public string $projectDir,
        public string $queriesDir,
        public string $outputDir,
        public string $namespace,
        public string $client,
        public bool $dumpOrThrows = false,
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
        public bool $indexByDirective = false,
        public bool $addUnknownCaseToEnums = false,
        public bool $dumpEnumIsMethods = false,
        public ?object $introspectionClient = null,
    ) {}

    public static function create(
        Schema | string $schema,
        string $projectDir,
        string $queriesDir,
        string $outputDir,
        string $namespace,
        string $client,
    ) : self {
        return new self(
            $schema,
            $projectDir,
            $queriesDir,
            $outputDir,
            $namespace,
            $client,
        );
    }

    public function enableDumpOrThrows() : self
    {
        return $this->with('dumpOrThrows', true);
    }

    public function enableDumpDefinition() : self
    {
        return $this->with('dumpDefinition', true);
    }

    public function enableUseNodeNameForEdgeNodes() : self
    {
        return $this->with('useNodeNameForEdgeNodes', true);
    }

    public function enableUseConnectionNameForConnections() : self
    {
        return $this->with('useConnectionNameForConnections', true);
    }

    public function enableUseEdgeNameForEdges() : self
    {
        return $this->with('useEdgeNameForEdges', true);
    }

    public function enableAddNodesOnConnections() : self
    {
        return $this->with('addNodesOnConnections', true);
    }

    public function enableAddSymfonyExcludeAttribute() : self
    {
        return $this->with('addSymfonyExcludeAttribute', true);
    }

    public function enableIndexByDirective() : self
    {
        return $this->with('indexByDirective', true);
    }

    public function enableAddUnknownCaseToEnums() : self
    {
        return $this->with('addUnknownCaseToEnums', true);
    }

    public function enableDumpEnumIsMethods() : self
    {
        return $this->with('dumpEnumIsMethods', true);
    }

    public function withScalar(string $name, Type $type, ?Type $payloadType = null) : self
    {
        $scalars = $this->scalars;
        $scalars[$name] = [$type, $payloadType ?? $type];

        return $this->with('scalars', $scalars);
    }

    public function withInputObjectType(string $name, Type $type) : self
    {
        $inputObjectTypes = $this->inputObjectTypes;
        $inputObjectTypes[$name] = $type;

        return $this->with('inputObjectTypes', $inputObjectTypes);
    }

    public function withObjectType(string $name, Type $payloadShape, Type $payloadType) : self
    {
        $objectTypes = $this->objectTypes;
        $objectTypes[$name] = [$payloadShape, $payloadType];

        return $this->with('objectTypes', $objectTypes);
    }

    public function withEnumType(string $name, Type $type) : self
    {
        $enumTypes = $this->enumTypes;
        $enumTypes[$name] = $type;

        return $this->with('enumTypes', $enumTypes);
    }

    public function withIgnoreType(string $type) : self
    {
        $ignoreTypes = $this->ignoreTypes;
        $ignoreTypes[] = $type;

        return $this->with('ignoreTypes', $ignoreTypes);
    }

    public function withTypeInitializer(TypeInitializer\TypeInitializer $typeInitializer) : self
    {
        $typeInitializers = $this->typeInitializers;
        $typeInitializers[] = $typeInitializer;

        return $this->with('typeInitializers', $typeInitializers);
    }

    public function withIntrospectionClient(object $client) : self
    {
        return $this->with('introspectionClient', $client);
    }

    /**
     * Replace with clone with when in PHP 8.5
     */
    private function with(string $name, mixed $value) : self
    {
        $clone = new ReflectionClass($this)->newInstanceWithoutConstructor();

        $vars = get_object_vars($this);
        $vars[$name] = $value;

        foreach ($vars as $varName => $varValue) {
            // @phpstan-ignore property.dynamicName
            $clone->$varName = $varValue;
        }

        return $clone;
    }
}
