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
     * @var list<Node>
     */
    public array $nodes {
        get => $this->nodes ??= array_map(fn($item) => new Node($item), $this->data['nodes']);
    }

    /**
     * @param array{
     *     'nodes': list<array{
     *         '__typename': string,
     *         'merged': bool,
     *         'number': int,
     *         'title': string,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
