<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

final readonly class NodeNotFoundExceptionPlan
{
    public function __construct(
        public string $path,
    ) {}
}
