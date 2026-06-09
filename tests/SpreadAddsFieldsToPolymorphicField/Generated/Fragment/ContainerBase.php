<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Fragment\ContainerBase\Item;

// This file was automatically generated and should not be edited.

final class ContainerBase
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public Item $item {
        get => $this->item ??= new Item($this->data['item']);
    }

    /**
     * @param array{
     *     'id': string,
     *     'item': array{
     *         'id': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
