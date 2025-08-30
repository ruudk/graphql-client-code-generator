<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Ruudk\GraphQLCodeGenerator\Planner\Plan\DataClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\ErrorClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\ExceptionClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\OperationClassPlan;
use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Represents a planned GraphQL operation
 */
final readonly class OperationPlan
{
    /**
     * @param array<string, SymfonyType> $variables
     */
    public function __construct(
        public string $operationName,
        public string $operationType,
        public string $queryClassName,
        public string $operationDefinition,
        public array $variables,
        public DataClassPlan $dataClass,
        public OperationClassPlan $operationClass,
        public ErrorClassPlan $errorClass,
        public ?ExceptionClassPlan $exceptionClass,
    ) {}
}
