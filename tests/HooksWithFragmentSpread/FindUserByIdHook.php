<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

#[Hook(name: 'findUserById')]
final readonly class FindUserByIdHook
{
    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private array $users = [],
    ) {}

    public function __invoke(string $id) : ?User
    {
        return $this->users[$id] ?? null;
    }
}
