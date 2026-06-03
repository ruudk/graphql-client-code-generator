<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data\Container\Item;

// This file was automatically generated and should not be edited.

final class AsVariantA
{
    public string $valueA {
        get => $this->valueA ??= $this->data['valueA'];
    }

    /**
     * @param array{
     *     '__typename': 'VariantA',
     *     'valueA': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
