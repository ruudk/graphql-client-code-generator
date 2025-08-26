<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferReversalDetails;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Fragment\TransferReversalDetails\Resolution\Total;

// This file was automatically generated and should not be edited.

final class Resolution
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
     * @param array{
     *     'createdAt': string,
     *     'id': string,
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
