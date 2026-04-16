<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures;

use Ruudk\GraphQLCodeGenerator\PHPStan\Generated\Data;
use Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeQuery;

/**
 * Source of the generated classes — every access below must be allowed.
 */
final readonly class AllowedController
{
    public function run() : string
    {
        $query = new SomeQuery('ruud');
        $data = $query->execute();
        $inline = new Data('inline');

        return $data->name . $inline->name;
    }
}
