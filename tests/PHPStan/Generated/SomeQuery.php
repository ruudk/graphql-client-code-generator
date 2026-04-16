<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan\Generated;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures\AllowedController;

#[Generated(
    source: AllowedController::class,
    restricted: true,
)]
final readonly class SomeQuery
{
    public function __construct(public string $name)
    {
    }

    public function execute() : Data
    {
        return new Data($this->name);
    }
}
