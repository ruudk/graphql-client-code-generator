<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\FindDiscountCodeByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Fragment\ShowPaymentFlow\Order;

// This file was automatically generated and should not be edited.

final class ShowPaymentFlow
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public Order $order {
        get => $this->order ??= new Order($this->data['order'], $this->hooks);
    }

    /**
     * @param array{
     *     'id': string,
     *     'order': array{
     *         'discountId': string,
     *         'id': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param array{
     *     'findDiscountCodeById': FindDiscountCodeByIdHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
