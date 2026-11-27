<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Fragment\PayoutOrder\OrderItem;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class PayoutOrder
{
    public OrderItem $orderItem {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->orderItem ??= $this->data['orderItem'] !== null ? new OrderItem($this->data['orderItem']) : throw NodeNotFoundException::create('Payout', 'orderItem');
    }

    /**
     * @param array{
     *     'orderItem': null|array{
     *         'id': string,
     *         'order': null|array{
     *             'id': string,
     *             'status': string,
     *             ...,
     *         },
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
