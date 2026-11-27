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
     * @param null|non-empty-list<SymfonyType> $groupByType
     * @param list<non-empty-list<string>> $groupBy
     * @param list<array<int, string>> $indexByThrowWhenNull
     * @param list<array<int, string>> $groupByThrowWhenNull
     */
    public function __construct(
        public string $outputDirectory,
        public string $fqcn,
        public string $path,
        public ?array $indexByType = null,
        public array $indexBy = [],
        public ?array $groupByType = null,
        public array $groupBy = [],
        public array $indexByThrowWhenNull = [],
        public array $groupByThrowWhenNull = [],
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
            'indexByThrowWhenNull' => [],
            'groupByThrowWhenNull' => [],
        ]);
    }

    /**
     * @param null|list<SymfonyType> $indexByType
     * @param list<list<string>> $indexBy
     * @param list<array<int, string>> $throwWhenNull
     */
    public function withIndexBy(?array $indexByType, array $indexBy, array $throwWhenNull = []): self
    {
        return clone($this, [
            'indexByType' => $indexByType,
            'indexBy' => $indexBy,
            'indexByThrowWhenNull' => $throwWhenNull,
        ]);
    }

    /**
     * @param non-empty-list<SymfonyType> $groupByType
     * @param non-empty-list<non-empty-list<string>> $groupBy
     * @param list<array<int, string>> $throwWhenNull
     */
    public function withGroupBy(array $groupByType, array $groupBy, array $throwWhenNull): self
    {
        return clone($this, [
            'groupByType' => $groupByType,
            'groupBy' => $groupBy,
            'groupByThrowWhenNull' => $throwWhenNull,
        ]);
    }
}
