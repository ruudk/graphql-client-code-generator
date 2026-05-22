<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Console;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

/**
 * A hook that is registered but never referenced by an `@hook` directive — the
 * fixture for `EnsureSyncCommandTest`'s unused-hook detection. It is never
 * invoked, so `__invoke` takes a loose `object` rather than its generated
 * `requires` data class (which is not committed anywhere).
 */
#[Hook(
    name: 'unusedHookForTesting',
    requires: <<<'GRAPHQL'
        fragment UnusedHookProbe on Project {
          id
        }
        GRAPHQL
)]
final readonly class UnusedHookForTesting
{
    public function __invoke(object $project) : string
    {
        return 'unused';
    }
}
