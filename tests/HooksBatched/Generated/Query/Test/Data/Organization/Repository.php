<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Query\Test\Data\Organization;

use Ruudk\GraphQLCodeGenerator\HooksBatched\Access;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\RepositoryAccessFields;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\RepositoryOwnerId;
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
     *     ...,
     * } $data
     * @param array{
     *     computeAccess: HookLoader<RepositoryAccessFields, Access>,
     *     findUserById: HookLoader<RepositoryOwnerId, null|User>,
     *     ...<string, HookLoader<mixed, mixed>>,
     * } $loaders
     */
    public function __construct(
        private readonly array $data,
        private readonly array $loaders,
    ) {}

    /**
     * @internal
     */
    public function buildRepositoryAccessFields() : RepositoryAccessFields
    {
        return new RepositoryAccessFields($this->data);
    }

    /**
     * @internal
     */
    public function buildRepositoryOwnerId() : RepositoryOwnerId
    {
        return new RepositoryOwnerId($this->data);
    }
}
