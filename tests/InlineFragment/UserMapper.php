<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLFragment;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Fragment\UserName56995d\UserName;

final readonly class UserMapper
{
    private const string FRAGMENT = <<<'GRAPHQL'
        fragment UserName on User {
          id
          firstName
          lastName
        }
        GRAPHQL;

    /**
     * @param list<UserName> $users
     * @return list<string>
     */
    public function mapMany(
        #[GeneratedGraphQLFragment(self::FRAGMENT)]
        array $users,
    ) : array {
        return array_map(
            fn(UserName $u) => $u->firstName . ' ' . $u->lastName,
            $users,
        );
    }
}
