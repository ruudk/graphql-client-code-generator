<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemTransferState\TransferReversal;

// This file was automatically generated and should not be edited.

final class AdminViewSystemTransferState
{
    /**
     * @var list<TransferReversal>
     */
    public array $transferReversals {
        get => $this->transferReversals ??= array_map(fn($item) => new TransferReversal($item), $this->data['transferReversals']);
    }

    /**
     * @param array{
     *     'transferReversals': list<array{
     *         'id': scalar,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
