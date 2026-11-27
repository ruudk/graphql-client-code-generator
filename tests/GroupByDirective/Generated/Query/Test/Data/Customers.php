<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\Customers\Edge;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\Customers\Edge\Node;

// This file was automatically generated and should not be edited.

final class Customers
{
    /**
     * @var array<string, list<Edge>>
     */
    public array $edges {
        get => $this->edges ??= (function() {
            $result = [];
            foreach ($this->data['edges'] as $item) {
                $result[$item['node']['country']][] = new Edge($item);
            }

            return $result;
        })();
    }

    /**
     * @var array<string, list<Node>>
     */
    public array $nodes {
        get => array_map(fn($edges) => array_map(fn($edge) => $edge->node, $edges), $this->edges);
    }

    /**
     * @param array{
     *     'edges': list<array{
     *         'node': array{
     *             'country': string,
     *             'id': int,
     *             'name': string,
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
