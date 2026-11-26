<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\Invoices68bfaf\Data;

use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Fragment\InvoiceRow;

// This file was automatically generated and should not be edited.

final class Bill
{
    public InvoiceRow $invoiceRow {
        get => $this->invoiceRow ??= new InvoiceRow($this->data);
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
