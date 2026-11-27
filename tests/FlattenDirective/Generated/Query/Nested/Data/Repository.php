<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Query\Nested\Data;

// This file was automatically generated and should not be edited.

final class Repository
{
    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public string $ownerName {
        get => $this->ownerName ??= $this->data['owner']['ownerName'];
    }

    /**
     * @param array{
     *     'name': string,
     *     'owner': array{
     *         'ownerName': string,
     *         ...,
     *     },
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
