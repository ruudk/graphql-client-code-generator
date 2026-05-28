<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\Data\Thing\AsVariantA;
use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\Data\Thing\AsVariantB;

// This file was automatically generated and should not be edited.

final class Thing
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsVariantA $asVariantA {
        get => $this->asVariantA ??= $this->data['__typename'] === 'VariantA' ? new AsVariantA($this->data, $this->hooks) : null;
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
     *     'realFieldA': string,
     * }|array{
     *     '__typename': 'VariantB',
     *     'id': string,
     *     'realFieldB': string,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
