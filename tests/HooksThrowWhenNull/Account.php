<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull;

final readonly class Account
{
    public function __construct(
        public string $id,
        public string $plan,
    ) {}
}
