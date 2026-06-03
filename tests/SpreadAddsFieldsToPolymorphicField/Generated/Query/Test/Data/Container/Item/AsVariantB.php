<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data\Container\Item;

// This file was automatically generated and should not be edited.

final class AsVariantB
{
    public string $valueB {
        get => $this->valueB ??= $this->data['valueB'];
    }

    /**
     * @param array{
     *     '__typename': 'VariantB',
     *     'valueB': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
