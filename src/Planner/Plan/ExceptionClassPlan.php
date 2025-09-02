<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

final readonly class ExceptionClassPlan
{
    public function __construct(
        public string $path,
        public string $namespace,
        public string $className,
    ) {}
}
