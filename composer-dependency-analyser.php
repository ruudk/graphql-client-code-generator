<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

$config->addPathToScan(__DIR__ . '/bin', isDev: false);

$config->ignoreErrorsOnPackage('twig/twig', [ErrorType::DEV_DEPENDENCY_IN_PROD]);

// phpstan/phpdoc-parser is never referenced directly. Symfony's TypeResolver
// only enables @return / @param PHPDoc parsing when this package is loadable,
// which the @hook directive relies on to resolve collection return types.
$config->ignoreErrorsOnPackage('phpstan/phpdoc-parser', [ErrorType::UNUSED_DEPENDENCY]);

return $config;
