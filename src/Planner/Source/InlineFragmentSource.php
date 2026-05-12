<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Source;

final readonly class InlineFragmentSource
{
    public function __construct(
        public string $class,
        public string $method,
        public string $parameter,
        public string $hash,
    ) {}
}
