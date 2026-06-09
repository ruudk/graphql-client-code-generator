<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data\Container;

use Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data\Container\Item\AsVariantA;
use Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data\Container\Item\AsVariantB;

// This file was automatically generated and should not be edited.

final class Item
{
    public ?AsVariantA $asVariantA {
        get => $this->asVariantA ??= $this->data['__typename'] === 'VariantA' ? new AsVariantA($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asVariantA
     */
    public bool $isVariantA {
        get => $this->isVariantA ??= $this->data['__typename'] === 'VariantA';
    }

    public ?AsVariantB $asVariantB {
        get => $this->asVariantB ??= $this->data['__typename'] === 'VariantB' ? new AsVariantB($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asVariantB
     */
    public bool $isVariantB {
        get => $this->isVariantB ??= $this->data['__typename'] === 'VariantB';
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     '__typename': 'VariantA',
     *     'id': string,
     *     'valueA': string,
     * }|array{
     *     '__typename': 'VariantB',
     *     'id': string,
     *     'valueB': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
