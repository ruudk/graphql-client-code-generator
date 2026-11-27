<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture;

final readonly class ProjectId implements NodeId
{
    public function __construct(
        public int $id,
    ) {}
}
