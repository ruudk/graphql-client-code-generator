<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Type\Schema;
use Symfony\Component\TypeInfo\Type;

final readonly class Config
{
    /**
     * @param array<string, Type|array{Type, Type}> $scalars
     * @param array<string, Type> $inputObjectTypes
     * @param array<string, array{Type, Type}> $objectTypes
     * @param array<string, Type> $enumTypes
     * @param list<string> $ignoreTypes
     * @param list<TypeInitializer\TypeInitializer> $typeInitializers
     */
    public function __construct(
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
        public bool $indexByDirective = true,
        public bool $addUnknownCaseToEnums = true,
    ) {}
}
