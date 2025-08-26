<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemTransferReversalRow\Transfer;

// This file was automatically generated and should not be edited.

final class AdminViewSystemTransferReversalRow
{
    public Transfer $transfer {
        get => $this->transfer ??= new Transfer($this->data['transfer']);
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
