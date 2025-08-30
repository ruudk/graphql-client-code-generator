<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Attribute;

use Attribute;

#[Attribute(flags: Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class GeneratedGraphQLClient
{
    public function __construct(
        public string $operation,
    ) {}
}
