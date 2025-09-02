<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Input\Fixture;

use Override;
use Stringable;

final readonly class Name implements Stringable
{
    public function __construct(
        public string $name,
    ) {}

    #[Override]
    public function __toString() : string
    {
        return $this->name;
    }
}
