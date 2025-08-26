<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\Test\Data\ProjectConnection\ProjectEdge;

// This file was automatically generated and should not be edited.

final class Project
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'id': string,
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
