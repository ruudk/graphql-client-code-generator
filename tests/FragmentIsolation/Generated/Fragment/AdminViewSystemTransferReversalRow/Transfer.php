<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemTransferReversalRow;

// This file was automatically generated and should not be edited.

final class Transfer
{
    public int|string|float|bool $metadata {
        get => $this->metadata ??= $this->data['metadata'];
    }

    /**
     * @param array{
     *     'metadata': scalar,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
