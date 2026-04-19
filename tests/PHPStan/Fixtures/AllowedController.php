<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan\Fixtures;

use Ruudk\GraphQLCodeGenerator\PHPStan\Generated\Data;
use Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeFragment;
use Ruudk\GraphQLCodeGenerator\PHPStan\Generated\SomeQuery;

/**
 * Source of the generated query — query/data accesses below must be allowed.
 * SomeFragment originates from a Twig template, so touching it from this
 * controller must still fire restriction errors.
 */
final readonly class AllowedController
{
    public function run() : string
    {
        $query = new SomeQuery('ruud');
        $data = $query->execute();
        $inline = new Data('inline');
        $twig = new SomeFragment('from twig');

        return $data->name . $inline->name . $twig->title;
    }
}
