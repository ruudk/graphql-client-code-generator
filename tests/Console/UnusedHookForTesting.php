<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Console;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

#[Hook(name: 'unusedHookForTesting')]
final readonly class UnusedHookForTesting
{
    public function __invoke(string $id) : string
    {
        return $id;
    }
}
