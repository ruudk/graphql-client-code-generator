<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\Data\Viewer;

use JsonSerializable;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Enum\Role;

// This file was automatically generated and should not be edited.

final class AsUser implements JsonSerializable
{
    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    public Role $role {
        get => $this->role ??= Role::from($this->data['role']);
    }

    /**
     * @param array{
     *     '__typename': 'User',
     *     'login': string,
     *     'role': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}

    /**
     * @return array{
     *     'login': string,
     *     'role': Role,
     * }
     */
    public function jsonSerialize() : array
    {
        return [
            'login' => $this->login,
            'role' => $this->role,
        ];
    }
}
