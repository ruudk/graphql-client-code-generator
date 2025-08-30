<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;

// This file was automatically generated and should not be edited.

final readonly class SearchQuery {
    public const string OPERATION_NAME = 'Search';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Search {
          search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
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
        
        GRAPHQL;

    public function __construct(
        private GitHubClient $client,
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
