<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class AsApplication
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Application'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public string $url {
        get => $this->url ??= $this->data['url'];
    }

    /**
     * @param array{
     *     '__typename': 'Application',
     *     'name': string,
     *     'url': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
