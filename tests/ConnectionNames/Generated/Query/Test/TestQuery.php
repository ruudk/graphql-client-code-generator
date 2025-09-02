<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          viewer {
            id
            name
            role
            createdAt
          }
          projects {
            edges {
              cursor
              node {
                id
                name
                status
              }
            }
            pageInfo {
              hasNextPage
              hasPreviousPage
              startCursor
              endCursor
            }
          }
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
