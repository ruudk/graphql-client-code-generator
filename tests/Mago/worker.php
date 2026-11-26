<?php

declare(strict_types=1);

use Mago\Sdk\Worker;
use Ruudk\GraphQLCodeGenerator\Mago\GraphQLExtension;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

new Worker(GraphQLExtension::create([dirname(__DIR__) . '/PHPStan/config.php']))->run();
