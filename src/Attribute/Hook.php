<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Hook
{
    public function __construct(
        public string $name,
    ) {}
}
