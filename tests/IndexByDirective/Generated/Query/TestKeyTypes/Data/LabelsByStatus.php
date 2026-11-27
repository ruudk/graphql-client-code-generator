<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestKeyTypes\Data;

use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Enum\LabelStatus;

// This file was automatically generated and should not be edited.

final class LabelsByStatus
{
    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public LabelStatus $status {
        get => $this->status ??= LabelStatus::from($this->data['status']);
    }

    /**
     * @param array{
     *     'name': string,
     *     'status': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
