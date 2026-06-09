<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Fragment\ContainerBase;

// This file was automatically generated and should not be edited.

final class Item
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'id': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
