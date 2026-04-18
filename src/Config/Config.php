<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use Closure;
use GraphQL\Type\Schema;
use Ruudk\GraphQLCodeGenerator\TypeInitializer;
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
     * @param list<string> $inlineProcessingDirectories
     * @param list<string> $twigProcessingDirectories
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
        public bool $addGeneratedAttribute = false,
        public bool $indexByDirective = false,
        public bool $addUnknownCaseToEnums = false,
        public bool $dumpEnumIsMethods = false,
        public ?object $introspectionClient = null,
        public array $inlineProcessingDirectories = [],
        public array $twigProcessingDirectories = [],
        public bool $formatOperationFiles = false,
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
        return clone ($this, [
            'dumpOrThrows' => true,
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
}
