<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Fragment\PayoutOrder;

use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Fragment\PayoutOrder\OrderItem\Order;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class OrderItem
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public Order $order {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->order ??= $this->data['order'] !== null ? new Order($this->data['order']) : throw NodeNotFoundException::create('OrderItem', 'order');
    }

    /**
     * @param array{
     *     'id': string,
     *     'order': null|array{
     *         'id': string,
     *         'status': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
