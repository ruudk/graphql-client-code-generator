<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Generated\Fragment;

// This file was automatically generated and should not be edited.

final class AppUrl
{
    public AppName $appName {
        get => $this->appName ??= new AppName($this->data);
    }

    public string $url {
        get => $this->url ??= $this->data['url'];
    }

    /**
     * @param array{
     *     'name': string,
     *     'url': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
