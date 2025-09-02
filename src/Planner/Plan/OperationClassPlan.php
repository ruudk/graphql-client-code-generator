<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use Ruudk\GraphQLCodeGenerator\Planner\Source\FileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class OperationClassPlan
{
    /**
     * @param array<string, SymfonyType> $variables
     */
    public function __construct(
        public string $path,
        public string $className,
        public string $operationNamepaceName,
        public string $operationName,
        public string $operationType,
        public string $operationDefinition,
        public array $variables,
        public FileSource | InlineSource $source,
    ) {}
}
