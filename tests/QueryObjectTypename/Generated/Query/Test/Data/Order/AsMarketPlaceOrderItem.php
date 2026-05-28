<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\Order;

use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\Order\AsMarketPlaceOrderItem\FxFee;

// This file was automatically generated and should not be edited.

final class AsMarketPlaceOrderItem
{
    public ?FxFee $fxFee {
        get => $this->fxFee ??= $this->data['fxFee'] !== null ? new FxFee($this->data['fxFee']) : null;
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     '__typename': 'MarketPlaceOrderItem',
     *     'fxFee': null|array{
     *         '__typename': string,
     *         ...<int|string, mixed>,
     *     },
     *     'id': string,
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
