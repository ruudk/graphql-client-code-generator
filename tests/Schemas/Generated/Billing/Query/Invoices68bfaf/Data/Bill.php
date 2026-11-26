<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\Invoices68bfaf\Data;

// This file was automatically generated and should not be edited.

final class Bill
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public int $total {
        get => $this->total ??= $this->data['total'];
    }

    /**
     * @param array{
     *     'id': string,
     *     'total': int,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
