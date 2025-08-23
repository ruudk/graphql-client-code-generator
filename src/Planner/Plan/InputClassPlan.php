<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class InputClassPlan
{
    /**
     * @param array<string, array{type: SymfonyType, required: bool, description: ?string}> $fields
     */
    public function __construct(
        public string $relativePath,
        public string $typeName,
        public ?string $description,
        public bool $isOneOf,
        public array $fields,
    ) {}
}
