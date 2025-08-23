<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment;

// This file was automatically generated and should not be edited.

final class ApplicationDetails
{
    public string $url {
        get => $this->url ??= $this->data['url'];
    }

    /**
     * @param array{
     *     'url': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
