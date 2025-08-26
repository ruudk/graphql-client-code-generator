<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferDetails;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferReversalDetails;

// This file was automatically generated and should not be edited.

final class TransferReversal
{
    public TransferReversalDetails $transferReversalDetails {
        get => $this->transferReversalDetails ??= new TransferReversalDetails($this->data);
    }

    /**
     * @param array{
     *     'createdAt': string,
     *     'id': string,
     *     'resolutions': null|list<array{
     *         'createdAt': string,
     *         'id': string,
     *         'total': array{
     *             'amount': string,
     *             'currency': string,
     *         },
     *     }>,
     *     'returnMethod': null|string,
     *     'returnedAt': null|string,
     *     'state': string,
     *     'total': array{
     *         'amount': string,
     *         'currency': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
