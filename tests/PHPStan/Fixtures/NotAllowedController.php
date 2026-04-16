<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures;

use Ruudk\GraphQLCodeGenerator\PHPStan\Generated\Data;
use Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeQuery;

/**
 * Not the source — each restricted access below must fire a PHPStan error.
 */
final readonly class NotAllowedController
{
    public function run() : string
    {
        $query = new SomeQuery('ruud');
        $data = $query->execute();
        $inline = new Data('inline');

        return $data->name . $inline->name;
    }
}
