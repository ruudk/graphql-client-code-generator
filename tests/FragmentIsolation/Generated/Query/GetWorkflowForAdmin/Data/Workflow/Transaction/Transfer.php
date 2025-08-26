<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data\Workflow\Transaction;

use Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data\Workflow\Transaction\Transfer\Customer;

// This file was automatically generated and should not be edited.

final class Transfer
{
    public Customer $customer {
        get => $this->customer ??= new Customer($this->data['customer']);
    }

    public int|string|float|bool $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'customer': array{
     *         'id': scalar,
     *     },
     *     'id': scalar,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
