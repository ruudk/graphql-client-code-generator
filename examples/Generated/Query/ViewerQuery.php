<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer\Data;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;

// This file was automatically generated and should not be edited.

final readonly class ViewerQuery {
    public function __construct(
        private GitHubClient $client,
    ) {}

    public function execute() : Data
    {
        $data = $this->client->graphql(
            <<<'GRAPHQL'
                query Viewer {
                  viewer {
                    __typename
                    login
                  }
                }
                
                GRAPHQL,
            [
            ],
            'Viewer',
        );

        return new Data($data['data'] ?? [], $data['errors'] ?? []);
    }
}
