<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

final readonly class EnumClassPlan
{
    /**
     * @param array<string, array{value: string, description: ?string}> $values
     */
    public function __construct(
        public string $relativePath,
        public string $typeName,
        public ?string $description,
        public array $values,
    ) {}
}
