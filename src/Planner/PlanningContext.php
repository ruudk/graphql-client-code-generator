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
     * @param list<string> $groupBy
     */
    public function __construct(
        public string $outputDirectory,
        public string $fqcn,
        public string $path,
        public ?array $indexByType = null,
        public array $indexBy = [],
        public ?SymfonyType $groupByType = null,
        public array $groupBy = [],
    ) {}

    public function withPath(string $path): self
    {
        return clone($this, [
            'path' => $path,
        ]);
    }

    public function withSubDirectory(string $className): self
    {
        return clone($this, [
            'outputDirectory' => $this->outputDirectory . '/' . $className,
            'fqcn' => $this->fqcn . '\\' . $className,
            'indexByType' => null,
            'indexBy' => [],
            'groupByType' => null,
            'groupBy' => [],
        ]);
    }

    /**
     * @param null|list<SymfonyType> $indexByType
     * @param list<list<string>> $indexBy
     */
    public function withIndexBy(?array $indexByType, array $indexBy): self
    {
        return clone($this, [
            'indexByType' => $indexByType,
            'indexBy' => $indexBy,
        ]);
    }

    /**
     * @param list<string> $groupBy
     */
    public function withGroupBy(SymfonyType $groupByType, array $groupBy): self
    {
        return clone($this, [
            'groupByType' => $groupByType,
            'groupBy' => $groupBy,
        ]);
    }
}
