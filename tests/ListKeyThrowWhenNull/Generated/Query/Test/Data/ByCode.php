<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data\ByCode\OrderItem;

// This file was automatically generated and should not be edited.

final class ByCode
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public OrderItem $orderItem {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->orderItem ??= $this->data['orderItem'] !== null ? new OrderItem($this->data['orderItem']) : throw NodeNotFoundException::create('Payout', 'orderItem');
    }

    /**
     * @param array{
     *     'id': string,
     *     'orderItem': null|array{
     *         'code': null|string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
