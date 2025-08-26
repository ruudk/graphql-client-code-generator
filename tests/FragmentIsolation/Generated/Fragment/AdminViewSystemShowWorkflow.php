<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemShowWorkflow\Transaction;

// This file was automatically generated and should not be edited.

final class AdminViewSystemShowWorkflow
{
    public int|string|float|bool $id {
        get => $this->id ??= $this->data['id'];
    }

    public ?Transaction $transaction {
        get => $this->transaction ??= $this->data['transaction'] !== null ? new Transaction($this->data['transaction']) : null;
    }

    /**
     * @param array{
     *     'id': scalar,
     *     'transaction': null|array{
     *         'transfers': list<array{
     *             'canBeCollected': bool,
     *             'id': scalar,
     *             'transferReversals': list<array{
     *                 'id': scalar,
     *                 'transfer': array{
     *                     'metadata': scalar,
     *                 },
     *             }>,
     *         }>,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
