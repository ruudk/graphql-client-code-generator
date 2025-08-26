<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemShowWorkflow\Transaction;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemShowWorkflow\Transaction\Transfer\TransferReversal;
use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemTransferRow;

// This file was automatically generated and should not be edited.

final class Transfer
{
    public AdminViewSystemTransferRow $adminViewSystemTransferRow {
        get => $this->adminViewSystemTransferRow ??= new AdminViewSystemTransferRow($this->data);
    }

    public int|string|float|bool $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @var list<TransferReversal>
     */
    public array $transferReversals {
        get => $this->transferReversals ??= array_map(fn($item) => new TransferReversal($item), $this->data['transferReversals']);
    }

    /**
     * @param array{
     *     'canBeCollected': bool,
     *     'id': scalar,
     *     'transferReversals': list<array{
     *         'id': scalar,
     *         'transfer': array{
     *             'metadata': scalar,
     *         },
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
