<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Query\Test\Data\Node;

use Ruudk\GraphQLCodeGenerator\HooksInterface\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Hook\ProjectOwnerId;
use Ruudk\GraphQLCodeGenerator\HooksInterface\User;

// This file was automatically generated and should not be edited.

final class AsProject
{
    public ?User $owner {
        get => $this->owner ??= $this->hooks['findOwner']->__invoke($this->buildProjectOwnerId());
    }

    /**
     * @param array{
     *     '__typename': 'Project',
     *     'ownerId': string,
     *     ...,
     * } $data
     * @param array{
     *     'findOwner': FindOwnerHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}

    /**
     * @internal
     */
    public function buildProjectOwnerId() : ProjectOwnerId
    {
        return new ProjectOwnerId($this->data);
    }
}
