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
        get {
            if (isset($this->asVariantA)) {
                return $this->asVariantA;
            }

            if ($this->data['__typename'] !== 'VariantA') {
                return $this->asVariantA = null;
            }

            if (! array_key_exists('realFieldA', $this->data)) {
                return $this->asVariantA = null;
            }

            return $this->asVariantA = new AsVariantA($this->data, $this->hooks);
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->asVariantA
     */
    public bool $isVariantA {
        get => $this->isVariantA ??= $this->data['__typename'] === 'VariantA';
    }

    public ?AsVariantB $asVariantB {
        get {
            if (isset($this->asVariantB)) {
                return $this->asVariantB;
            }

            if ($this->data['__typename'] !== 'VariantB') {
                return $this->asVariantB = null;
            }

            if (! array_key_exists('realFieldB', $this->data)) {
                return $this->asVariantB = null;
            }

            return $this->asVariantB = new AsVariantB($this->data);
        }
    }

    /**
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
     *     '__typename': string,
     *     'id': string,
     *     'realFieldA'?: string,
     *     'realFieldB'?: string,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
