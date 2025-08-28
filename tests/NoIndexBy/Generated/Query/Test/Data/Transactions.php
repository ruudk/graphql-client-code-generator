<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge;
use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class Transactions
{
    /**
     * @var array<string,Edge>
     */
    public array $edges {
        get => $this->edges ??= array_combine(
            array_map(fn($item) => $item['node']['id'], $this->data['edges']),
            array_map(fn($item) => new Edge($item), $this->data['edges']),
        );
    }

    /**
     * @var array<string,Node>
     */
    public array $nodes {
        get => array_map(fn($edge) => $edge->node, $this->edges);
    }

    /**
     * @param array{
     *     'edges': list<array{
     *         'node': array{
     *             'id': string,
     *             'workflow': null|array{
     *                 'request': null|array{
     *                     'id': string,
     *                     'items': list<array{
     *                         '__typename': string,
     *                         'id': string,
     *                     }>,
     *                 },
     *             },
     *         },
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
