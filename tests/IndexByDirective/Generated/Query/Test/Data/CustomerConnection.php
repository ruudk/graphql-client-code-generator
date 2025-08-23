<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test\Data\CustomerConnection\CustomerEdge;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test\Data\CustomerConnection\CustomerEdge\Customer;

// This file was automatically generated and should not be edited.

final class CustomerConnection
{
    /**
     * @var array<int,CustomerEdge>
     */
    public array $edges {
        get => $this->edges ??= array_combine(
            array_map(fn($item) => $item['node']['id'], $this->data['edges']),
            array_map(fn($item) => new CustomerEdge($item), $this->data['edges']),
        );
    }

    /**
     * @var array<int,Customer>
     */
    public array $nodes {
        get => array_map(fn($edge) => $edge->node, $this->edges);
    }

    /**
     * @param array{
     *     'edges': list<array{
     *         'node': array{
     *             'id': int,
     *             'name': string,
     *         },
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
