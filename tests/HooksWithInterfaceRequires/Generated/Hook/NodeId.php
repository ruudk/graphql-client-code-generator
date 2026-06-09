<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Hook;

// This file was automatically generated and should not be edited.

final class NodeId
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     'id': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
