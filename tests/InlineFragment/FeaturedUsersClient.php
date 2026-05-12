<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\FeaturedUsers5a9829\FeaturedUsersQuery;

final readonly class FeaturedUsersClient
{
    private const string OPERATION = <<<'GRAPHQL'
        query FeaturedUsers {
            featuredUsers {
                ...UserName
            }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION)]
        private FeaturedUsersQuery $query,
        private UserMapper $mapper,
    ) {}

    /**
     * @return list<string>
     */
    public function getDisplayNames() : array
    {
        return $this->mapper->mapMany(
            array_map(fn($user) => $user->userName, $this->query->execute()->featuredUsers),
        );
    }
}
