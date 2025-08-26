<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment;

// This file was automatically generated and should not be edited.

final class AdminViewSystemTransferRow
{
    public AdminViewSystemTransferState $adminViewSystemTransferState {
        get => $this->adminViewSystemTransferState ??= new AdminViewSystemTransferState($this->data);
    }

    public bool $canBeCollected {
        get => $this->canBeCollected ??= $this->data['canBeCollected'];
    }

    /**
     * @param array{
     *     'canBeCollected': bool,
     *     'transferReversals': list<array{
     *         'id': scalar,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
