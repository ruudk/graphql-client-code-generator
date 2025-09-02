<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Attribute;

use Attribute;

#[Attribute(flags: Attribute::TARGET_CLASS)]
final readonly class Generated
{
    public function __construct(
        public string $source,
        public bool $restricted = false,
        public bool $restrictInstantiation = false,
    ) {
    }

}
