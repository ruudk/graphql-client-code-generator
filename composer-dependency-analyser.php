<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration();

$config->addPathToScan(__DIR__ . '/bin', isDev: false);

return $config;
