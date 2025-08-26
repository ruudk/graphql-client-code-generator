<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Fragment\AdminViewSystemShowWorkflow;
use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data\Workflow\Transaction;

// This file was automatically generated and should not be edited.

final class Workflow
{
    public AdminViewSystemShowWorkflow $adminViewSystemShowWorkflow {
        get => $this->adminViewSystemShowWorkflow ??= new AdminViewSystemShowWorkflow($this->data);
    }

    public ?Transaction $transaction {
        get => $this->transaction ??= $this->data['transaction'] !== null ? new Transaction($this->data['transaction']) : null;
    }

    /**
     * @param array{
     *     'id': scalar,
     *     'transaction': null|array{
     *         'transfers': list<array{
     *             'canBeCollected': bool,
     *             'customer': array{
     *                 'id': scalar,
     *             },
     *             'id': scalar,
     *             'transferReversals': list<array{
     *                 'id': scalar,
     *                 'transfer': array{
     *                     'metadata': scalar,
     *                 },
     *             }>,
     *         }>,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
