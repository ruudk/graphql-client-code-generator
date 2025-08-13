<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;

// This file was automatically generated and should not be edited.

final readonly class SearchQuery {
    public function __construct(
        private GitHubClient $client,
    ) {}

    public function execute() : Data
    {
        $data = $this->client->graphql(
            <<<'GRAPHQL'
                query Search {
                  search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
                    __typename
                    nodes {
                      __typename
                      ... on Issue {
                        number
                        title
                      }
                      ...PullRequestInfo
                    }
                  }
                }
                
                fragment PullRequestInfo on PullRequest {
                  number
                  title
                  merged
                }
                
                GRAPHQL,
            [
            ],
            'Search',
        );

        return new Data($data['data'] ?? [], $data['errors'] ?? []);
    }
}
