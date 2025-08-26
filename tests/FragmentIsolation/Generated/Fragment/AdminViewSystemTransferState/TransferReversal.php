<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemTransferState;

// This file was automatically generated and should not be edited.

final class TransferReversal
{
    public int|string|float|bool $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'id': scalar,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
