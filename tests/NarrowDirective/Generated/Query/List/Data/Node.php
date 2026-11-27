<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\List\Data;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class Node
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @var array{
     *     '__typename': string,
     *     'description': null|string,
     *     'name': string,
     *     ...,
     * }
     */
    private readonly array $data;

    /**
     * @param array{
     *     '__typename': string,
     *     'description'?: null|string,
     *     'name'?: string,
     *     ...,
     * } $data
     * @throws NodeNotFoundException
     */
    public function __construct(
        array $data,
    ) {
        if ($data['__typename'] !== 'Event' || ! array_key_exists('description', $data) || ! array_key_exists('name', $data)) {
            throw NodeNotFoundException::unexpectedType('Query', 'nodes', 'Event', $data['__typename']);
        }

        $this->data = $data;
    }
}
