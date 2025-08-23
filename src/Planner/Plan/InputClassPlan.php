<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use GraphQL\Type\Definition\InputObjectType;

final readonly class InputClassPlan
{
    public function __construct(
        public string $relativePath,
        public InputObjectType $inputType,
    ) {}
}
