<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\Order\AsMarketPlaceOrderItem;

// This file was automatically generated and should not be edited.

final class Order
{
    public ?AsMarketPlaceOrderItem $asMarketPlaceOrderItem {
        get {
            if (isset($this->asMarketPlaceOrderItem)) {
                return $this->asMarketPlaceOrderItem;
            }

            if ($this->data['__typename'] !== 'MarketPlaceOrderItem') {
                return $this->asMarketPlaceOrderItem = null;
            }

            if (! array_key_exists('id', $this->data)) {
                return $this->asMarketPlaceOrderItem = null;
            }

            if (! array_key_exists('fxFee', $this->data)) {
                return $this->asMarketPlaceOrderItem = null;
            }

            return $this->asMarketPlaceOrderItem = new AsMarketPlaceOrderItem($this->data);
        }
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
     *     '__typename': string,
     *     'fxFee'?: null|array{
     *         '__typename': string,
     *     },
     *     'id'?: string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
