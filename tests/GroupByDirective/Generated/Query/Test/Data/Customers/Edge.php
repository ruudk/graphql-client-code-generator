<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\Customers;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\Customers\Edge\Node;

// This file was automatically generated and should not be edited.

final class Edge
{
    public Node $node {
        get => $this->node ??= new Node($this->data['node']);
    }

    /**
     * @param array{
     *     'node': array{
     *         'country': string,
     *         'id': int,
     *         'name': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
