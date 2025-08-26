<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails\Data\Transaction\Transfer;

// This file was automatically generated and should not be edited.

final class TransferReversal
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $state {
        get => $this->state ??= $this->data['state'];
    }

    /**
     * @param array{
     *     'id': string,
     *     'state': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
