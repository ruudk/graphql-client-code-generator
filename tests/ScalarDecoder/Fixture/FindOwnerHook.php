<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Hook\ProjectOwnerId;

#[Hook(name: 'findOwner', requires: <<<'GRAPHQL'
    fragment ProjectOwnerId on Project {
      ownerId
    }
    GRAPHQL)]
final readonly class FindOwnerHook
{
    /**
     * @param array<int, string> $names
     */
    public function __construct(
        private array $names = [],
    ) {}

    public function __invoke(ProjectOwnerId $project): ?string
    {
        return $this->names[$project->ownerId->id] ?? null;
    }
}
