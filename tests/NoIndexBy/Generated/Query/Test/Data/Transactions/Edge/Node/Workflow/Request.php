<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node\Workflow;

use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node\Workflow\Request\Item;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class Request
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @var list<Item>
     */
    public array $items {
        get => $this->items ??= array_map(fn($item) => new Item($item), $this->data['items']);
    }

    /**
     * @param array{
     *     'id': string,
     *     'items': list<array{
     *         '__typename': string,
     *         'id': string,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getId() : string
    {
        return $this->id;
    }

    /**
     * @return list<Item>
     */
    public function getItems() : array
    {
        return $this->items;
    }
}
