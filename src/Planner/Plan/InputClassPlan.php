<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class InputClassPlan
{
    /**
     * @param array<string, SymfonyType> $fields
     * @param list<string> $required
     */
    public function __construct(
        public string $path,
        public string $typeName,
        public ?string $description,
        public bool $isOneOf,
        public array $fields,
        public array $required,
    ) {}
}
