<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment;

// This file was automatically generated and should not be edited.

final class ProjectStateView
{
    public ?string $state {
        get => $this->state ??= $this->data['state'] !== null ? $this->data['state'] : null;
    }

    /**
     * @param array{
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
