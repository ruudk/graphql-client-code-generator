<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

final readonly class Access
{
    public function __construct(
        public string $ownerId,
        public string $reviewerId,
        public bool $ownerIsReviewer,
    ) {}
}
