<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Expected\Query;

use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Expected\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public function __construct(
        private TestClient $client,
    ) {}

    public function execute(
        bool $includeAdmin,
        bool $skipAdmin,
    ) : Data {
        $data = $this->client->graphql(
            <<<'GRAPHQL'
                query Test($includeAdmin: Boolean!, $skipAdmin: Boolean!) {
                  viewer {
                    name
                  }
                  user2: user {
                    name
                  }
                  admin @include(if: $includeAdmin) {
                    name
                  }
                  admin2: admin @skip(if: $skipAdmin) {
                    name
                  }
                }
                
                GRAPHQL,
            [
                'includeAdmin' => $includeAdmin,
                'skipAdmin' => $skipAdmin,
            ],
            'Test',
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
