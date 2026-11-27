<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data\Event\Types;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data\Event\Types\Edge\Node;

// This file was automatically generated and should not be edited.

final class Edge
{
    public string $cursor {
        get => $this->cursor ??= $this->data['cursor'];
    }

    public Node $node {
        get => $this->node ??= new Node($this->data['node']);
    }

    /**
     * @param array{
     *     'cursor': string,
     *     'node': array{
     *         'id': string,
     *         'title': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
