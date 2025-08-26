<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Query;

use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.
// Based on tests/Fragments/Test.graphql

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          viewer {
            __typename
            ...ViewerName
            ...UserDetails
            ...ApplicationDetails
          }
          projects {
            ...ProjectView
          }
        }
        
        fragment ViewerName on Viewer {
          name
        }
        
        fragment ApplicationDetails on Application {
          url
        }
        
        fragment UserDetails on User {
          login
        }
        
        fragment ProjectView on Project {
          name
          description
          ...ProjectStateView
        }
        
        fragment ProjectStateView on Project {
          state
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
