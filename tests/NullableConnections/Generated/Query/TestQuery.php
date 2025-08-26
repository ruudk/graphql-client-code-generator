<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query;

use Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.
// Based on tests/NullableConnections/Test.graphql

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          projects {
            edges {
              cursor
              node {
                id
                name
              }
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
