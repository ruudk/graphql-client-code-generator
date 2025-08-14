<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Search\Data\Search\Node;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

/**
 * {
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
#[Exclude]
final class Search
{
    public string $__typename {
        get => $this->data['__typename'];
    }

    /**
     * @var list<Node>
     */
    public array $nodes {
        get => array_map(fn($item) => new Node($item), $this->data['nodes']);
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
