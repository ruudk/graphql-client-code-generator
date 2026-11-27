<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\Customers\Edge;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\Customers\Edge\Node;

// This file was automatically generated and should not be edited.

final class Customers
{
    /**
     * @var array<string, array<string, list<Edge>>>
     */
    public array $edges {
        get => $this->edges ??= (function() {
            $result = [];
            foreach ($this->data['edges'] as $item) {
                $result[$item['node']['tier']][$item['node']['country']][] = new Edge($item);
            }

            return $result;
        })();
    }

    /**
     * @var array<string, array<string, list<Node>>>
     */
    public array $nodes {
        get => array_map(fn($group) => array_map(fn($edges) => array_map(fn($edge) => $edge->node, $edges), $group), $this->edges);
    }

    /**
     * @param array{
     *     'edges': list<array{
     *         'node': array{
     *             'country': string,
     *             'id': int,
     *             'name': string,
     *             'tier': string,
     *             ...,
     *         },
     *         ...,
     *     }>,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
