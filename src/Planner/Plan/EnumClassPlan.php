<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use GraphQL\Type\Definition\EnumType;

final readonly class EnumClassPlan
{
    public function __construct(
        public string $relativePath,
        public string $typeName,
        public EnumType $enumType,
    ) {}
}
