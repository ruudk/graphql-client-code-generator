<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\Data\Event\Types;

// This file was automatically generated and should not be edited.

final class Event
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public Types $types {
        get => $this->types ??= new Types($this->data['types']);
    }

    /**
     * @var array{
     *     '__typename': string,
     *     'id': string,
     *     'name': string,
     *     'types': array{
     *         'edges': list<array{
     *             'cursor': string,
     *             'node': array{
     *                 'id': string,
     *                 'title': string,
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     ...,
     * }
     */
    private readonly array $data;

    /**
     * @param array{
     *     '__typename': string,
     *     'id': string,
     *     'name'?: string,
     *     'types'?: array{
     *         'edges': list<array{
     *             'cursor': string,
     *             'node': array{
     *                 'id': string,
     *                 'title': string,
     *                 ...,
     *             },
     *             ...,
     *         }>,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @throws NodeNotFoundException
     */
    public function __construct(
        array $data,
    ) {
        if ($data['__typename'] !== 'Event' || ! array_key_exists('name', $data) || ! array_key_exists('types', $data)) {
            throw NodeNotFoundException::unexpectedType('Query', 'event', 'Event', $data['__typename']);
        }

        $this->data = $data;
    }
}
