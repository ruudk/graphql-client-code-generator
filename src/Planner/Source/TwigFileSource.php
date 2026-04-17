<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Source;

final readonly class TwigFileSource
{
    public function __construct(
        public string $relativeFilePath,
    ) {}
}
