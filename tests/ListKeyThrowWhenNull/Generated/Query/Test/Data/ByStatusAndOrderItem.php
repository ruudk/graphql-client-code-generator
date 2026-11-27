<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Fragment\PayoutOrder;

// This file was automatically generated and should not be edited.

final class ByStatusAndOrderItem
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public PayoutOrder $payoutOrder {
        get => $this->payoutOrder ??= new PayoutOrder($this->data);
    }

    /**
     * @param array{
     *     'id': string,
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
