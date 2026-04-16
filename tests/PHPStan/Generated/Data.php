<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan\Generated;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures\AllowedController;

#[Generated(
    source: AllowedController::class,
    restricted: true,
    restrictInstantiation: true,
)]
final class Data
{
    public function __construct(
        public string $name,
    ) {
    }
}
