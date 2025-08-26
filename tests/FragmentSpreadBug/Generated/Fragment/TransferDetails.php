<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferDetails\Total;
use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferDetails\TransferReversal;

// This file was automatically generated and should not be edited.

final class TransferDetails
{
    public string $createdAt {
        get => $this->createdAt ??= $this->data['createdAt'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public Total $total {
        get => $this->total ??= new Total($this->data['total']);
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
     *     'total': array{
     *         'amount': string,
     *         'currency': string,
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
     *             },
     *         }>,
     *         'returnMethod': null|string,
     *         'returnedAt': null|string,
     *         'state': string,
     *         'total': array{
     *             'amount': string,
     *             'currency': string,
     *         },
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
