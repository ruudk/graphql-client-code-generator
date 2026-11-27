<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Query\Test\Data;

// This file was automatically generated and should not be edited.

final class User
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
