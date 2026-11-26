<?php

declare(strict_types=1);

use Mago\Sdk\Worker;
use Ruudk\GraphQLCodeGenerator\Mago\GraphQLExtension;

require dirname(__DIR__) . '/vendor/autoload.php';

new Worker(GraphQLExtension::create([
    dirname(__DIR__) . '/examples/config.php',
    dirname(__DIR__) . '/tests/config.php',
]))->run();
