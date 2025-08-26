<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions;

use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class Edge
{
    public Node $node {
        get => $this->node ??= new Node($this->data['node']);
    }

    /**
     * @param array{
     *     'node': array{
     *         'id': string,
     *         'workflow': null|array{
     *             'request': null|array{
     *                 'id': string,
     *                 'items': list<array{
     *                     '__typename': string,
     *                     'id': string,
     *                 }>,
     *             },
     *         },
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getNode() : Node
    {
        return $this->node;
    }
}
