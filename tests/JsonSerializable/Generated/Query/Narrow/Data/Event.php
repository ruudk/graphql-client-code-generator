<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Narrow\Data;

use JsonSerializable;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class Event implements JsonSerializable
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @var array{
     *     '__typename': string,
     *     'id': string,
     *     'name': string,
     *     ...,
     * }
     */
    private readonly array $data;

    /**
     * @param array{
     *     '__typename': string,
     *     'id'?: string,
     *     'name'?: string,
     *     ...,
     * } $data
     * @throws NodeNotFoundException
     */
    public function __construct(
        array $data,
    ) {
        if ($data['__typename'] !== 'Event' || ! array_key_exists('id', $data) || ! array_key_exists('name', $data)) {
            throw NodeNotFoundException::unexpectedType('Query', 'event', 'Event', $data['__typename']);
        }

        $this->data = $data;
    }

    /**
     * @return array{
     *     'id': string,
     *     'name': string,
     * }
     */
    public function jsonSerialize() : array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
