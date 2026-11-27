<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data\ByCode;

use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class OrderItem
{
    public string $code {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->code ??= $this->data['code'] !== null ? $this->data['code'] : throw NodeNotFoundException::create('OrderItem', 'code');
    }

    /**
     * @param array{
     *     'code': null|string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
