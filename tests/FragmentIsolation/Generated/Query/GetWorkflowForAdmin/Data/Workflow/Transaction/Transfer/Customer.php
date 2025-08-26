<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentIsolation\Generated\Query\GetWorkflowForAdmin\Data\Workflow\Transaction\Transfer;

// This file was automatically generated and should not be edited.

final class Customer
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
