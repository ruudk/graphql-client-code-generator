<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Enum\UserRole;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public int|string|float|bool $createdAt {
        get => $this->createdAt ??= $this->data['createdAt'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public UserRole $role {
        get => $this->role ??= UserRole::from($this->data['role']);
    }

    /**
     * @param array{
     *     'createdAt': scalar,
     *     'id': string,
     *     'name': string,
     *     'role': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
