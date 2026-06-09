<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInterface;

final readonly class User
{
    public function __construct(
        public string $id,
    ) {}
}
