<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Expected\Query;

use Ruudk\GraphQLCodeGenerator\Optimization\Expected\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public function __construct(
        private TestClient $client,
    ) {}

    public function execute() : Data
    {
        $data = $this->client->graphql(
            <<<'GRAPHQL'
                query Test {
                  viewer {
                    __typename
                    id
                    idAlias: id
                    name
                    ... on User {
                      login
                      name
                    }
                    ...AppUrl
                  }
                  projects {
                    name
                    description
                    state
                  }
                }
                
                fragment AppUrl on Application {
                  url
                  ...AppName
                }
                
                fragment AppName on Application {
                  name
                }
                
                GRAPHQL,
            [
            ],
            'Test',
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
