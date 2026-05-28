<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class AsApplication
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
}
