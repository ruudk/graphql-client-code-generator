<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails\Data;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails\Data\Transaction\Transfer;

// This file was automatically generated and should not be edited.

final class Transaction
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $state {
        get => $this->state ??= $this->data['state'];
    }

    /**
     * @var list<Transfer>
     */
    public array $transfers {
        get => $this->transfers ??= array_map(fn($item) => new Transfer($item), $this->data['transfers']);
    }

    /**
     * @param array{
     *     'id': string,
     *     'state': string,
     *     'transfers': list<array{
     *         'createdAt': string,
     *         'id': string,
     *         'state': string,
     *         'total': array{
     *             'amount': string,
     *             'currency': string,
     *         },
     *         'transferReversals': list<array{
     *             'createdAt': string,
     *             'id': string,
     *             'resolutions': null|list<array{
     *                 'createdAt': string,
     *                 'id': string,
     *                 'total': array{
     *                     'amount': string,
     *                     'currency': string,
     *                 },
     *             }>,
     *             'returnMethod': null|string,
     *             'returnedAt': null|string,
     *             'state': string,
     *             'total': array{
     *                 'amount': string,
     *                 'currency': string,
     *             },
     *         }>,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
