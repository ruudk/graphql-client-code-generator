<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Fragment\ProjectSummary;

// This file was automatically generated and should not be edited.

final class Creator
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'id': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
