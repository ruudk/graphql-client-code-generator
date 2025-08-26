<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemShowWorkflow;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemShowWorkflow\Transaction\Transfer;

// This file was automatically generated and should not be edited.

final class Transaction
{
    /**
     * @var list<Transfer>
     */
    public array $transfers {
        get => $this->transfers ??= array_map(fn($item) => new Transfer($item), $this->data['transfers']);
    }

    /**
     * @param array{
     *     'transfers': list<array{
     *         'canBeCollected': bool,
     *         'id': scalar,
     *         'transferReversals': list<array{
     *             'id': scalar,
     *             'transfer': array{
     *                 'metadata': scalar,
     *             },
     *         }>,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
