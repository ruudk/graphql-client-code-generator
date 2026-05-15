<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

$config->addPathToScan(__DIR__ . '/bin', isDev: false);

$config->ignoreErrorsOnPackage('twig/twig', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
$config->ignoreErrorsOnPackage('vincentlanglet/twig-cs-fixer', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
$config->ignoreErrorsOnPackage('friendsofphp/php-cs-fixer', [ErrorType::DEV_DEPENDENCY_IN_PROD]);

// The custom php-cs-fixer fixer uses ext-tokenizer's T_* constants via the
// php-cs-fixer extension API. php-cs-fixer already requires ext-tokenizer;
// the library's runtime code does not, so it is not a real dependency.
$config->ignoreErrorsOnExtensionAndPath(
    'ext-tokenizer',
    __DIR__ . '/src/PhpCsFixer/GraphQLHeredocFixer.php',
    [ErrorType::SHADOW_DEPENDENCY],
);

// phpstan/phpdoc-parser is never referenced directly. Symfony's TypeResolver
// only enables @return / @param PHPDoc parsing when this package is loadable,
// which the @hook directive relies on to resolve collection return types.
$config->ignoreErrorsOnPackage('phpstan/phpdoc-parser', [ErrorType::UNUSED_DEPENDENCY]);

return $config;
