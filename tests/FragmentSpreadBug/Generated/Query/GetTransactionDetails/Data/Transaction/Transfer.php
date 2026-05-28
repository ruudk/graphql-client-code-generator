<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails\Data\Transaction;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferDetails;
use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails\Data\Transaction\Transfer\TransferReversal;

// This file was automatically generated and should not be edited.

final class Transfer
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $state {
        get => $this->state ??= $this->data['state'];
    }

    public TransferDetails $transferDetails {
        get => $this->transferDetails ??= new TransferDetails($this->data);
    }

    /**
     * @var list<TransferReversal>
     */
    public array $transferReversals {
        get => $this->transferReversals ??= array_map(fn($item) => new TransferReversal($item), $this->data['transferReversals']);
    }

    /**
     * @param array{
     *     'createdAt': string,
     *     'id': string,
     *     'state': string,
     *     'total': array{
     *         'amount': string,
     *         'currency': string,
     *         ...<int|string, mixed>,
     *     },
     *     'transferReversals': list<array{
     *         'createdAt': string,
     *         'id': string,
     *         'resolutions': null|list<array{
     *             'createdAt': string,
     *             'id': string,
     *             'total': array{
     *                 'amount': string,
     *                 'currency': string,
     *                 ...<int|string, mixed>,
     *             },
     *             ...<int|string, mixed>,
     *         }>,
     *         'returnMethod': null|string,
     *         'returnedAt': null|string,
     *         'state': string,
     *         'total': array{
     *             'amount': string,
     *             'currency': string,
     *             ...<int|string, mixed>,
     *         },
     *         ...<int|string, mixed>,
     *     }>,
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
