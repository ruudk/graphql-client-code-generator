<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use GraphQL\Language\AST\TypeNode;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class OperationClassPlan
{
    /**
     * @param array<non-empty-string, array{required: bool, typeNode: null|TypeNode, type: SymfonyType}> $variables
     */
    public function __construct(
        public string $path,
        public string $fqcn,
        public string $operationName,
        public string $operationType,
        public string $queryClassName,
        public string $operationDefinition,
        public array $variables,
        public string $relativeFilePath,
        public string $source,
    ) {}
}
