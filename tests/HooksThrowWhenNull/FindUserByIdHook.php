<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Hook\CustomerUserId;

#[Hook(name: 'findUserById', requires: <<<'GRAPHQL'
    fragment CustomerUserId on Customer {
      userId
    }
    GRAPHQL)]
final readonly class FindUserByIdHook
{
    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private array $users = [],
    ) {}

    public function __invoke(CustomerUserId $customer): ?User
    {
        return $this->users[$customer->userId] ?? null;
    }
}
