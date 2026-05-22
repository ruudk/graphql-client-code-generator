<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

/**
 * Batched hook taking two fields. Records every batch it is invoked with so the
 * test can measure its invocation count.
 */
#[Hook(name: 'computeAccess', batched: true)]
final class ComputeAccessHook
{
    /**
     * The `$inputs` array of every `__invoke` call, in order.
     *
     * @var list<array<int, array{string, string}>>
     */
    public array $batches = [];

    /**
     * @param array<int, array{string, string}> $inputs
     * @return iterable<int, Access>
     */
    public function __invoke(array $inputs) : iterable
    {
        $this->batches[] = $inputs;

        foreach ($inputs as $key => [$ownerId, $reviewerId]) {
            yield $key => new Access($ownerId, $reviewerId, $ownerId === $reviewerId);
        }
    }
}
