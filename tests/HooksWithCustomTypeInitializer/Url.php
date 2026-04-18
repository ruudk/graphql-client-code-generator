<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer;

final readonly class Url
{
    public function __construct(
        public string $href,
    ) {}
}
