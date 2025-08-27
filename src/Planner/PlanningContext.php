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
        public bool $isGeneratingTopLevelFragment = false,
        public bool $isInsideFragmentContext = false,
    ) {}

    public function withPath(string $path) : self
    {
        return new self(
            $this->outputDirectory,
            $this->fqcn,
            $path,
            $this->indexByType,
            $this->indexBy,
            $this->isGeneratingTopLevelFragment,
            $this->isInsideFragmentContext,
        );
    }

    public function withSubDirectory(string $className) : self
    {
        return new self(
            $this->outputDirectory . '/' . $className,
            $this->fqcn . '\\' . $className,
            $this->path,
            null, // Reset indexBy - it should not be inherited by nested fields
            [], // Reset indexBy - it should not be inherited by nested fields
            false, // Reset flag for nested classes - they are not top-level fragments
            $this->isInsideFragmentContext, // Preserve fragment context flag
        );
    }

    /**
     * @param null|list<SymfonyType> $indexByType
     * @param list<list<string>> $indexBy
     */
    public function withIndexBy(?array $indexByType, array $indexBy) : self
    {
        return new self(
            $this->outputDirectory,
            $this->fqcn,
            $this->path,
            $indexByType,
            $indexBy,
            $this->isGeneratingTopLevelFragment,
            $this->isInsideFragmentContext,
        );
    }
}
