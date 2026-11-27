<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\Customers\Edge;

use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Enum\CustomerTier;

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

    public CustomerTier $tier {
        get => $this->tier ??= CustomerTier::from($this->data['tier']);
    }

    /**
     * @param array{
     *     'country': string,
     *     'id': int,
     *     'name': string,
     *     'tier': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
