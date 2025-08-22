<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query;

use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.
// Based on tests/InlineFragments/Test.graphql

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          viewer {
            __typename
            name
            ... on User {
              login
            }
            ... on Application {
              url
            }
          }
          projects {
            name
            description
            state
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
