<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use Ruudk\GraphQLCodeGenerator\Planner\Plan\ErrorClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\ExceptionClassPlan;
use Ruudk\GraphQLCodeGenerator\Planner\Plan\OperationClassPlan;

/**
 * Represents a planned GraphQL operation
 */
final readonly class OperationPlan
{
    public function __construct(
        public string $operationName,
        public OperationClassPlan $operationClass,
        public ErrorClassPlan $errorClass,
        public ?ExceptionClassPlan $exceptionClass,
    ) {}
}
