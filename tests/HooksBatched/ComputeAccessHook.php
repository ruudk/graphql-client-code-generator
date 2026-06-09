<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\RepositoryAccessFields;

/**
 * Batched hook taking two fields. Records every batch it is invoked with so the
 * test can measure its invocation count.
 */
#[Hook(name: 'computeAccess', requires: <<<'GRAPHQL'
    fragment RepositoryAccessFields on Repository {
      ownerId
      reviewerId
    }
    GRAPHQL, batched: true)]
final class ComputeAccessHook
{
    /**
     * The `$inputs` array of every `__invoke` call, in order.
     *
     * @var list<array<int, RepositoryAccessFields>>
     */
    public array $batches = [];

    /**
     * @param array<int, RepositoryAccessFields> $inputs
     * @return iterable<int, Access>
     */
    public function __invoke(array $inputs) : iterable
    {
        $this->batches[] = $inputs;

        foreach ($inputs as $key => $repository) {
            yield $key => new Access(
                $repository->ownerId,
                $repository->reviewerId,
                $repository->ownerId === $repository->reviewerId,
            );
        }
    }
}
