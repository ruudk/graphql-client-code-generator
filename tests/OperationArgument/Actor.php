<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument;

final readonly class Actor
{
    public function __construct(
        public string $id,
    ) {}
}
