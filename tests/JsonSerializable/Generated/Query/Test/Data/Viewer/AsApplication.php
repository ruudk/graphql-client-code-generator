<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Viewer;

use JsonSerializable;

// This file was automatically generated and should not be edited.

final class AsApplication implements JsonSerializable
{
    public string $url {
        get => $this->url ??= $this->data['url'];
    }

    /**
     * @param array{
     *     '__typename': 'Application',
     *     'url': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * @return array{
     *     'url': string,
     * }
     */
    public function jsonSerialize() : array
    {
        return [
            'url' => $this->url,
        ];
    }
}
