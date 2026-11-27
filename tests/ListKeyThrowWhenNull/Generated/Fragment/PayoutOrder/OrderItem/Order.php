<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Fragment\PayoutOrder\OrderItem;

use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Enum\OrderStatus;

// This file was automatically generated and should not be edited.

final class Order
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public OrderStatus $status {
        get => $this->status ??= OrderStatus::from($this->data['status']);
    }

    /**
     * @param array{
     *     'id': string,
     *     'status': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
