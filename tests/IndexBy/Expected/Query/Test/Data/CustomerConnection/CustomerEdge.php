<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexBy\Expected\Query\Test\Data\CustomerConnection;

use Ruudk\GraphQLCodeGenerator\IndexBy\Expected\Query\Test\Data\CustomerConnection\CustomerEdge\Customer;

// This file was automatically generated and should not be edited.

/**
 * ... on CustomerEdge {
 *   node {
 *     id
 *     name
 *   }
 * }
 */
final class CustomerEdge
{
    public Customer $node {
        get => $this->node ??= new Customer($this->data['node']);
    }

    /**
     * @param array{
     *     'node': array{
     *         'id': int,
     *         'name': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
