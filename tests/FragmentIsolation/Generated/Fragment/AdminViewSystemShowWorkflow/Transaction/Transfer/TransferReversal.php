<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemShowWorkflow\Transaction\Transfer;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemTransferReversalRow;

// This file was automatically generated and should not be edited.

final class TransferReversal
{
    public AdminViewSystemTransferReversalRow $adminViewSystemTransferReversalRow {
        get => $this->adminViewSystemTransferReversalRow ??= new AdminViewSystemTransferReversalRow($this->data);
    }

    /**
     * @param array{
     *     'transfer': array{
     *         'metadata': scalar,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
