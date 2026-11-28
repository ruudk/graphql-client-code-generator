<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Search;

use JsonSerializable;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Fragment\VenueName;

// This file was automatically generated and should not be edited.

final class AsVenue implements JsonSerializable
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public VenueName $venueName {
        get => $this->venueName ??= new VenueName($this->data);
    }

    /**
     * @param array{
     *     '__typename': 'Venue',
     *     'id': string,
     *     'title': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * @return array{
     *     'id': string,
     *     'title': string,
     * }
     */
    public function jsonSerialize() : array
    {
        return [
            'id' => $this->id,
            ...$this->venueName->jsonSerialize(),
        ];
    }
}
