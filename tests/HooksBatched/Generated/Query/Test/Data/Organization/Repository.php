<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Query\Test\Data\Organization;

use Ruudk\GraphQLCodeGenerator\HooksBatched\Access;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\HookLoader;
use Ruudk\GraphQLCodeGenerator\HooksBatched\User;

// This file was automatically generated and should not be edited.

final class Repository
{
    public Access $access {
        get => $this->access ??= $this->loaders['computeAccess']->resolve($this);
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ?User $owner {
        get => $this->owner ??= $this->loaders['findUserById']->resolve($this);
    }

    public string $ownerId {
        get => $this->ownerId ??= $this->data['ownerId'];
    }

    public string $reviewerId {
        get => $this->reviewerId ??= $this->data['reviewerId'];
    }

    /**
     * @param array{
     *     'id': string,
     *     'name': string,
     *     'ownerId': string,
     *     'reviewerId': string,
     *     ...<int|string, mixed>,
     * } $data
     * @param array{
     *     computeAccess: HookLoader<array{string, string}, Access>,
     *     findUserById: HookLoader<array{string}, null|User>,
     *     ...<string, HookLoader<array<int, mixed>, mixed>>,
     * } $loaders
     */
    public function __construct(
        private readonly array $data,
        private readonly array $loaders,
    ) {}
}
