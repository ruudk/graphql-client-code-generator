<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Query;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.
// Based on tests/FragmentBug/Test.graphql

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          projects {
            ...ProjectView
          }
        }
        
        fragment ProjectView on Project {
          name
          creator {
            __typename
            ... on User {
              name
            }
            ... on Admin {
              name
              role
            }
          }
          ...ProjectStateView
        }
        
        fragment ProjectStateView on Project {
          creator {
            id
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
