<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\RepositoryOwnerId;

/**
 * Batched hook. Records every batch it is invoked with so the test can measure
 * that it runs exactly once per operation, with every occurrence in that batch.
 */
#[Hook(name: 'findUserById', requires: <<<'GRAPHQL'
    fragment RepositoryOwnerId on Repository {
      ownerId
    }
    GRAPHQL, batched: true)]
final class FindUserByIdHook
{
    /**
     * The `$inputs` array of every `__invoke` call, in order.
     *
     * @var list<array<int, RepositoryOwnerId>>
     */
    public array $batches = [];

    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private readonly array $users = [],
    ) {}

    /**
     * @param array<int, RepositoryOwnerId> $inputs
     * @return iterable<int, ?User>
     */
    public function __invoke(array $inputs) : iterable
    {
        $this->batches[] = $inputs;

        foreach ($inputs as $key => $repository) {
            yield $key => $this->users[$repository->ownerId] ?? null;
        }
    }
}
