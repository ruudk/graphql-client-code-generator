<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferReversalDetails;

// This file was automatically generated and should not be edited.

final class Total
{
    public string $amount {
        get => $this->amount ??= $this->data['amount'];
    }

    public string $currency {
        get => $this->currency ??= $this->data['currency'];
    }

    /**
     * @param array{
     *     'amount': string,
     *     'currency': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
