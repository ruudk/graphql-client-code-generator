<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksBatched\Access;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\OrganizationId;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\RepositoryAccessFields;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\RepositoryOwnerId;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\HookLoader;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Query\Test\Data\Organization\Repository;
use Ruudk\GraphQLCodeGenerator\HooksBatched\OrgPlan;
use Ruudk\GraphQLCodeGenerator\HooksBatched\User;

// This file was automatically generated and should not be edited.

final class Organization
{
    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public OrgPlan $plan {
        get => $this->plan ??= $this->loaders['findOrgPlan']->resolve($this);
    }

    /**
     * @var list<Repository>
     */
    public array $repositories {
        get => $this->repositories ??= array_map(fn($item) => new Repository($item, $this->loaders), $this->data['repositories']);
    }

    /**
     * @param array{
     *     'id': string,
     *     'name': string,
     *     'repositories': list<array{
     *         'id': string,
     *         'name': string,
     *         'ownerId': string,
     *         'reviewerId': string,
     *         ...,
     *     }>,
     *     ...,
     * } $data
     * @param array{
     *     findOrgPlan: HookLoader<OrganizationId, OrgPlan>,
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
    public function buildOrganizationId() : OrganizationId
    {
        return new OrganizationId($this->data);
    }
}
