<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\MutationTypename\Generated\Mutation\Test\Data;

// This file was automatically generated and should not be edited.

final class WithExtraFields
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'name': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
