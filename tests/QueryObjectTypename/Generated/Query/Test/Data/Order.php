<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\Order\AsMarketPlaceOrderItem;

// This file was automatically generated and should not be edited.

final class Order
{
    public ?AsMarketPlaceOrderItem $asMarketPlaceOrderItem {
        get => $this->asMarketPlaceOrderItem ??= $this->data['__typename'] === 'MarketPlaceOrderItem' ? new AsMarketPlaceOrderItem($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asMarketPlaceOrderItem
     */
    public bool $isMarketPlaceOrderItem {
        get => $this->isMarketPlaceOrderItem ??= $this->data['__typename'] === 'MarketPlaceOrderItem';
    }

    /**
     * @param array{
     *     '__typename': 'MarketPlaceOrderItem',
     *     'fxFee': null|array{
     *         '__typename': string,
     *         ...,
     *     },
     *     'id': string,
     * }|array{
     *     '__typename': 'OtherOrderItem',
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
