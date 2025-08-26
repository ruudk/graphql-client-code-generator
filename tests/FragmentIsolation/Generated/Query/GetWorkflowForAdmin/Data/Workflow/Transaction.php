<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data\Workflow;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data\Workflow\Transaction\Transfer;

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
     *         'customer': array{
     *             'id': scalar,
     *         },
     *         'id': scalar,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
