<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectList;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;

// This file was automatically generated and should not be edited.

#[Generated(
    source: 'tests/Twig/templates/list.html.twig',
    restricted: true,
    restrictInstantiation: true,
)]
final class Viewer
{
    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'name': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
