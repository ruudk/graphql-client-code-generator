<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\SearchResultItemConnection\Node;

// This file was automatically generated and should not be edited.

/**
 * ... on SearchResultItemConnection {
 *   __typename
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
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    /**
     * @var list<Node>
     */
    public array $nodes {
        get => $this->nodes ??= array_map(fn($item) => new Node($item), $this->data['nodes']);
    }

    /**
     * @param array{
     *     '__typename': string,
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
