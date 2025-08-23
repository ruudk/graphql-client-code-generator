<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Fragment\ProjectView\Creator;

// This file was automatically generated and should not be edited.

final class AsUser
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     '__typename': 'User',
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
