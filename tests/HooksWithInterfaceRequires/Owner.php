<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires;

final readonly class Owner
{
    public function __construct(
        public string $nodeId,
        public string $name,
    ) {}
}
