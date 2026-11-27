<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\Data\Customers\Edge;

// This file was automatically generated and should not be edited.

final class Node
{
    public string $country {
        get => $this->country ??= $this->data['country'];
    }

    public int $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'country': string,
     *     'id': int,
     *     'name': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
