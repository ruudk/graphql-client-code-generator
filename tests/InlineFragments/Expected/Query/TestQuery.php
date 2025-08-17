<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query;

use Ruudk\GraphQLCodeGenerator\InlineFragments\Expected\Query\Test\Data;
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
