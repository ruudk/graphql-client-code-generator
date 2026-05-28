<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class AsUser
{
    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     '__typename': 'User',
     *     'login': string,
     *     'name': string,
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
