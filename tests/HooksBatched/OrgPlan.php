<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

final readonly class OrgPlan
{
    public function __construct(
        public string $organizationId,
        public string $tier,
    ) {}
}
