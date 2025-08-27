<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField\Data;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField\Data\CustomerConnection\CustomerEdge;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField\Data\CustomerConnection\CustomerEdge\Customer;

// This file was automatically generated and should not be edited.

final class CustomerConnection
{
    /**
     * @var array<int,array<string,CustomerEdge>>
     */
    public array $edges {
        get => $this->edges ??= (function() {
            $result = [];
            foreach ($this->data['edges'] as $item) {
                $result[$item['node']['id']][$item['node']['name']] = new CustomerEdge($item);
            }

            return $result;
        })();
    }

    /**
     * @var list<Customer>
     */
    public array $nodes {
        get {
            $nodes = [];
            foreach ($this->edges as $edgeGroup) {
                foreach ($edgeGroup as $edge) {
                    $nodes[] = $edge->node;
                }
            }

            return $nodes;
        }
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
