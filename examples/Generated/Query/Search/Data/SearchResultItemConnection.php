<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection\Node;

// This file was automatically generated and should not be edited.

/**
 * ... on SearchResultItemConnection {
 *   nodes {
 *     __typename
 *     ... on Issue {
 *       number
 *       title
 *     }
 *     ...PullRequestInfo
 *   }
 * }
 */
final class SearchResultItemConnection
{
    /**
     * @var null|list<null|Node>
     */
    public ?array $nodes {
        get => $this->nodes ??= $this->data['nodes'] !== null ? array_map(fn($item) => $item !== null ? new Node($item) : null, $this->data['nodes']) : null;
    }

    /**
     * @param array{
     *     'nodes': null|list<null|array{
     *         '__typename': 'App',
     *     }|array{
     *         '__typename': 'Discussion',
     *     }|array{
     *         '__typename': 'Issue',
     *         'number': int,
     *         'title': string,
     *     }|array{
     *         '__typename': 'MarketplaceListing',
     *     }|array{
     *         '__typename': 'Organization',
     *     }|array{
     *         '__typename': 'PullRequest',
     *         'merged': bool,
     *         'number': int,
     *         'title': string,
     *     }|array{
     *         '__typename': 'Repository',
     *     }|array{
     *         '__typename': 'User',
     *     }>,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
