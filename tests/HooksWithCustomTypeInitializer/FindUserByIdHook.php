<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Hook\ProjectCreatorId;

#[Hook(
    name: 'findUserById',
    requires: <<<'GRAPHQL'
        fragment ProjectCreatorId on Project {
          creator {
            id
          }
        }
        GRAPHQL
)]
final readonly class FindUserByIdHook
{
    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private array $users = [],
    ) {}

    public function __invoke(ProjectCreatorId $project) : ?User
    {
        return $this->users[$project->creator->id] ?? null;
    }
}
