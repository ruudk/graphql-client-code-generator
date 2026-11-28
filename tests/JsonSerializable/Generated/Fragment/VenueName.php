<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Fragment;

use JsonSerializable;

// This file was automatically generated and should not be edited.

final class VenueName implements JsonSerializable
{
    public string $title {
        get => $this->title ??= $this->data['title'];
    }

    /**
     * @param array{
     *     'title': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * @return array{
     *     'title': string,
     * }
     */
    public function jsonSerialize() : array
    {
        return [
            'title' => $this->title,
        ];
    }
}
