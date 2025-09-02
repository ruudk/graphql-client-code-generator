<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Source;

final readonly class FileSource
{
    public function __construct(
        public string $relativeFilePath,
    ) {}
}
