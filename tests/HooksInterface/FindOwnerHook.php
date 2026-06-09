<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInterface;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksInterface\Generated\Hook\ProjectOwnerId;

#[Hook(
    name: 'findOwner',
    requires: <<<'GRAPHQL'
        fragment ProjectOwnerId on Project {
          ownerId
        }
        GRAPHQL
)]
final readonly class FindOwnerHook
{
    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private array $users = [],
    ) {}

    public function __invoke(ProjectOwnerId $project) : ?User
    {
        return $this->users[$project->ownerId] ?? null;
    }
}
