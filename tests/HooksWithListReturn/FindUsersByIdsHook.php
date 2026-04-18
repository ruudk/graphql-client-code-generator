<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

#[Hook(name: 'findUsersByIds')]
final readonly class FindUsersByIdsHook
{
    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private array $users = [],
    ) {}

    /**
     * @param list<string> $ids
     * @return list<User>
     */
    public function __invoke(array $ids) : array
    {
        $out = [];

        foreach ($ids as $id) {
            if (isset($this->users[$id])) {
                $out[] = $this->users[$id];
            }
        }

        return $out;
    }
}
