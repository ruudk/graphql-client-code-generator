<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions\Edge\Node\Workflow\Request;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class Item
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'id': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    public function getId() : string
    {
        return $this->id;
    }
}
