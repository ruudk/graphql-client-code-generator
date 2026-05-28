<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection;

// This file was automatically generated and should not be edited.

/**
 * query Search {
 *   search(query: "repo:twigstan/twigstan", type: ISSUE, first: 10) {
 *     nodes {
 *       __typename
 *       ... on Issue {
 *         number
 *         title
 *       }
 *       ...PullRequestInfo
 *     }
 *   }
 * }
 */
final class Data
{
    public SearchResultItemConnection $search {
        get => $this->search ??= new SearchResultItemConnection($this->data['search']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'search': array{
     *         'nodes': null|list<null|array{
     *             '__typename': 'App',
     *         }|array{
     *             '__typename': 'Discussion',
     *         }|array{
     *             '__typename': 'Issue',
     *             'number': int,
     *             'title': string,
     *         }|array{
     *             '__typename': 'MarketplaceListing',
     *         }|array{
     *             '__typename': 'Organization',
     *         }|array{
     *             '__typename': 'PullRequest',
     *             'merged': bool,
     *             'number': int,
     *             'title': string,
     *         }|array{
     *             '__typename': 'Repository',
     *         }|array{
     *             '__typename': 'User',
     *         }>,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
