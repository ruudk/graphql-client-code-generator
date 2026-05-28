<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\Data\Thing;

// This file was automatically generated and should not be edited.

final class AsVariantB
{
    public string $realFieldB {
        get => $this->realFieldB ??= $this->data['realFieldB'];
    }

    /**
     * @param array{
     *     '__typename': 'VariantB',
     *     'realFieldB': string,
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
