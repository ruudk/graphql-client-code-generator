<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

/**
 * Batched hook taking a single field. Records every batch it is invoked with so
 * the test can measure that it runs exactly once per operation, with every
 * occurrence's input in that single batch.
 */
#[Hook(name: 'findUserById', batched: true)]
final class FindUserByIdHook
{
    /**
     * The `$inputs` array of every `__invoke` call, in order.
     *
     * @var list<array<int, array{string}>>
     */
    public array $batches = [];

    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private readonly array $users = [],
    ) {}

    /**
     * @param array<int, array{string}> $inputs
     * @return iterable<int, ?User>
     */
    public function __invoke(array $inputs) : iterable
    {
        $this->batches[] = $inputs;

        foreach ($inputs as $key => [$id]) {
            yield $key => $this->users[$id] ?? null;
        }
    }
}
