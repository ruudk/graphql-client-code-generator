<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use GraphQL\Language\AST\TypeNode;
use Ruudk\GraphQLCodeGenerator\Planner\Source\FileSource;
use Ruudk\GraphQLCodeGenerator\Planner\Source\InlineSource;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class OperationClassPlan
{
    /**
     * @param array<non-empty-string, array{required: bool, typeNode: null|TypeNode, type: SymfonyType}> $variables
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
