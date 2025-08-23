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
     * @param list<string> $indexBy
     */
    public function __construct(
        public string $outputDirectory,
        public string $fqcn,
        public string $path,
        public ?SymfonyType $indexByType = null,
        public array $indexBy = [],
    ) {}

    public function withPath(string $path) : self
    {
        return new self(
            $this->outputDirectory,
            $this->fqcn,
            $path,
            $this->indexByType,
            $this->indexBy,
        );
    }

    public function withSubDirectory(string $className) : self
    {
        return new self(
            $this->outputDirectory . '/' . $className,
            $this->fqcn . '\\' . $className,
            $this->path,
            $this->indexByType,
            $this->indexBy,
        );
    }

    /**
     * @param list<string> $indexBy
     */
    public function withIndexBy(?SymfonyType $indexByType, array $indexBy) : self
    {
        return new self(
            $this->outputDirectory,
            $this->fqcn,
            $this->path,
            $indexByType,
            $indexBy,
        );
    }
}
