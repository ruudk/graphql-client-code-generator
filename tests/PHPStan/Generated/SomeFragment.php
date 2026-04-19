<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan\Generated;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;

#[Generated(
    source: 'tests/PHPStan/templates/_some_fragment.html.twig',
    restricted: true,
    restrictInstantiation: true,
)]
final class SomeFragment
{
    public function __construct(
        public string $title,
    ) {
    }
}
