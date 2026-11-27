<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data\Event;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data\Event\Types\Edge;

// This file was automatically generated and should not be edited.

final class Types
{
    /**
     * @var list<Edge>
     */
    public array $edges {
        get => $this->edges ??= array_map(fn($item) => new Edge($item), $this->data['edges']);
    }

    /**
     * @param array{
     *     'edges': list<array{
     *         'cursor': string,
     *         'node': array{
     *             'id': string,
     *             'title': string,
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
