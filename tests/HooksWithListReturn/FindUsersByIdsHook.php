<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Hook\ProjectContributorIds;

#[Hook(
    name: 'findUsersByIds',
    requires: <<<'GRAPHQL'
        fragment ProjectContributorIds on Project {
          contributorIds
        }
        GRAPHQL
)]
final readonly class FindUsersByIdsHook
{
    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private array $users = [],
    ) {}

    /**
     * @return list<User>
     */
    public function __invoke(ProjectContributorIds $project) : array
    {
        $out = [];

        foreach ($project->contributorIds as $id) {
            if (isset($this->users[$id])) {
                $out[] = $this->users[$id];
            }
        }

        return $out;
    }
}
