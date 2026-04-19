<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Context for planning a selection set
 */
final readonly class PlanningContext
{
    /**
     * @param null|list<SymfonyType> $indexByType
     * @param list<list<string>> $indexBy
     */
    public function __construct(
        public string $outputDirectory,
        public string $fqcn,
        public string $path,
        public ?array $indexByType = null,
        public array $indexBy = [],
    ) {}

    public function withPath(string $path) : self
    {
        return clone ($this, [
            'path' => $path,
        ]);
    }

    public function withSubDirectory(string $className) : self
    {
        return clone ($this, [
            'outputDirectory' => $this->outputDirectory . '/' . $className,
            'fqcn' => $this->fqcn . '\\' . $className,
            'indexByType' => null,
            'indexBy' => [],
        ]);
    }

    /**
     * @param null|list<SymfonyType> $indexByType
     * @param list<list<string>> $indexBy
     */
    public function withIndexBy(?array $indexByType, array $indexBy) : self
    {
        return clone ($this, [
            'indexByType' => $indexByType,
            'indexBy' => $indexBy,
        ]);
    }
}
