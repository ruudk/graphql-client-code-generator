<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Hook\CustomerAccountId;

#[Hook(name: 'findAccountById', requires: <<<'GRAPHQL'
    fragment CustomerAccountId on Customer {
      accountId
    }
    GRAPHQL, batched: true)]
final readonly class FindAccountByIdHook
{
    /**
     * @param array<string, Account> $accounts
     */
    public function __construct(
        private array $accounts = [],
    ) {}

    /**
     * @param array<int, CustomerAccountId> $inputs
     * @return iterable<int, ?Account>
     */
    public function __invoke(array $inputs): iterable
    {
        foreach ($inputs as $key => $customer) {
            yield $key => $this->accounts[$customer->accountId] ?? null;
        }
    }
}
