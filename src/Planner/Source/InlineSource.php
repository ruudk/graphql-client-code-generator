<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Source;

final readonly class InlineSource
{
    public function __construct(
        public string $class,
        public string $hash,
    ) {}
}
