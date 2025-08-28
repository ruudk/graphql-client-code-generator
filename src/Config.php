<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Type\Schema;
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
     */
    private function __construct(
        public Schema | string $schema,
        public string $projectDir,
        public string $queriesDir,
        public string $outputDir,
        public string $namespace,
        public string $client,
        public bool $dumpMethods,
        public bool $dumpOrThrows,
        public bool $dumpDefinition,
        public bool $useNodeNameForEdgeNodes,
        public array $scalars,
        public array $inputObjectTypes,
        public array $objectTypes,
        public array $enumTypes,
        public array $ignoreTypes,
        public array $typeInitializers,
        public bool $useConnectionNameForConnections,
        public bool $useEdgeNameForEdges,
        public bool $addNodesOnConnections,
        public bool $addSymfonyExcludeAttribute,
        public bool $indexByDirective,
        public bool $addUnknownCaseToEnums,
        public bool $dumpEnumIsMethods,
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
            false,
            false,
            false,
            false,
            [],
            [],
            [],
            [],
            [],
            [],
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        );
    }

    public function enableDumpMethods() : self
    {
        // TODO Replace with clone with https://wiki.php.net/rfc/clone_with_v2 when on PHP 8.5
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            true,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableDumpOrThrows() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            true,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableDumpDefinition() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            true,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableUseNodeNameForEdgeNodes() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            true,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableUseConnectionNameForConnections() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            true,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableUseEdgeNameForEdges() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            true,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableAddNodesOnConnections() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            true,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableAddSymfonyExcludeAttribute() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            true,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableIndexByDirective() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            true,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableAddUnknownCaseToEnums() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            true,
            $this->dumpEnumIsMethods,
        );
    }

    public function enableDumpEnumIsMethods() : self
    {
        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            true,
        );
    }

    public function withScalar(string $name, Type $type, ?Type $payloadType = null) : self
    {
        $scalars = $this->scalars;
        $scalars[$name] = [$type, $payloadType ?? $type];

        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function withInputObjectType(string $name, Type $type) : self
    {
        $inputObjectTypes = $this->inputObjectTypes;
        $inputObjectTypes[$name] = $type;

        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function withObjectType(string $name, Type $payloadShape, Type $payloadType) : self
    {
        $objectTypes = $this->objectTypes;
        $objectTypes[$name] = [$payloadShape, $payloadType];

        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function withEnumType(string $name, Type $type) : self
    {
        $enumTypes = $this->enumTypes;
        $enumTypes[$name] = $type;

        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $enumTypes,
            $this->ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function withIgnoreType(string $type) : self
    {
        $ignoreTypes = $this->ignoreTypes;
        $ignoreTypes[] = $type;

        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $ignoreTypes,
            $this->typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }

    public function withTypeInitializer(TypeInitializer\TypeInitializer $typeInitializer) : self
    {
        $typeInitializers = $this->typeInitializers;
        $typeInitializers[] = $typeInitializer;

        return new self(
            $this->schema,
            $this->projectDir,
            $this->queriesDir,
            $this->outputDir,
            $this->namespace,
            $this->client,
            $this->dumpMethods,
            $this->dumpOrThrows,
            $this->dumpDefinition,
            $this->useNodeNameForEdgeNodes,
            $this->scalars,
            $this->inputObjectTypes,
            $this->objectTypes,
            $this->enumTypes,
            $this->ignoreTypes,
            $typeInitializers,
            $this->useConnectionNameForConnections,
            $this->useEdgeNameForEdges,
            $this->addNodesOnConnections,
            $this->addSymfonyExcludeAttribute,
            $this->indexByDirective,
            $this->addUnknownCaseToEnums,
            $this->dumpEnumIsMethods,
        );
    }
}
