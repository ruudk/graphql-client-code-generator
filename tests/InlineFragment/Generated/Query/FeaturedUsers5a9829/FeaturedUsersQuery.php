<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\FeaturedUsers5a9829;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineFragment\FeaturedUsersClient;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

#[Generated(
    source: FeaturedUsersClient::class,
    restricted: true,
)]
final readonly class FeaturedUsersQuery {
    public const string OPERATION_NAME = 'FeaturedUsers';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query FeaturedUsers {
          featuredUsers {
            ...UserName
          }
        }
        
        fragment UserName on User {
          id
          firstName
          lastName
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    /**
     * @api
     */
    public function execute() : Data
    {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
