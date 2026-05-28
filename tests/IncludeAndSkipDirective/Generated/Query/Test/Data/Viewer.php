<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'name': string,
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
