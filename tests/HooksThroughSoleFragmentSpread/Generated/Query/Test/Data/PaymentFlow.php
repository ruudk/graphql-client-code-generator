<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\FindDiscountCodeByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Fragment\ShowPaymentFlow;

// This file was automatically generated and should not be edited.

final class PaymentFlow
{
    public ShowPaymentFlow $showPaymentFlow {
        get => $this->showPaymentFlow ??= new ShowPaymentFlow($this->data, $this->hooks);
    }

    /**
     * @param array{
     *     'id': string,
     *     'order': array{
     *         'discountId': string,
     *         'id': string,
     *     },
     * } $data
     * @param array{
     *     'findDiscountCodeById': FindDiscountCodeByIdHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
