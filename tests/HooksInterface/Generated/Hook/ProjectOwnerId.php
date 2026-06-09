<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Hook;

// This file was automatically generated and should not be edited.

final class ProjectOwnerId
{
    public string $ownerId {
        get => $this->ownerId ??= $this->data['ownerId'];
    }

    /**
     * @param array{
     *     'ownerId': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
