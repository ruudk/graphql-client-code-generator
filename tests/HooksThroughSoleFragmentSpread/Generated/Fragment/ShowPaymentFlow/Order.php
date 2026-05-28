<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Fragment\ShowPaymentFlow;

use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\DiscountCode;
use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\FindDiscountCodeByIdHook;

// This file was automatically generated and should not be edited.

final class Order
{
    public ?DiscountCode $discountCode {
        get => $this->discountCode ??= $this->hooks['findDiscountCodeById']->__invoke($this->discountId);
    }

    public string $discountId {
        get => $this->discountId ??= $this->data['discountId'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'discountId': string,
     *     'id': string,
     *     ...<int|string, mixed>,
     * } $data
     * @param array{
     *     'findDiscountCodeById': FindDiscountCodeByIdHook,
     *     ...<int|string, mixed>,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
