<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Fragment\ContainerBase;
use Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data\Container\Item;

// This file was automatically generated and should not be edited.

final class Container
{
    public ContainerBase $containerBase {
        get => $this->containerBase ??= new ContainerBase($this->data);
    }

    public Item $item {
        get => $this->item ??= new Item($this->data['item']);
    }

    /**
     * @param array{
     *     'id': string,
     *     'item': array{
     *         '__typename': 'VariantA',
     *         'id': string,
     *         'valueA': string,
     *     }|array{
     *         '__typename': 'VariantB',
     *         'id': string,
     *         'valueB': string,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
