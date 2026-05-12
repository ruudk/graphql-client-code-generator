<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\ListUsers9908fe;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineFragment\ListUsersClient;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

#[Generated(
    source: ListUsersClient::class,
    restricted: true,
)]
final readonly class ListUsersQuery {
    public const string OPERATION_NAME = 'ListUsers';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query ListUsers {
          users {
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
