<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\ListUsers9908fe\ListUsersQuery;

final readonly class ListUsersClient
{
    private const string OPERATION = <<<'GRAPHQL'
        query ListUsers {
          users {
            ...UserName
          }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION)]
        private ListUsersQuery $query,
        private UserMapper $mapper,
    ) {}

    /**
     * @return list<string>
     */
    public function getDisplayNames() : array
    {
        return $this->mapper->mapMany(
            array_map(fn($user) => $user->userName, $this->query->execute()->users),
        );
    }
}
