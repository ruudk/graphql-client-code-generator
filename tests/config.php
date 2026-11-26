<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Symfony\Component\Finder\Finder;

$config = Config::create(dirname(__DIR__));
foreach (Finder::create()
    ->files()
    ->depth(1)
    ->in(__DIR__)
    ->name('*Test.php')
    ->sortByName() as $file) {
    $class = 'Ruudk\\GraphQLCodeGenerator\\' . $file->getRelativePath() . '\\' . $file->getFilenameWithoutExtension();

    if (!class_exists($class)) {
        continue;
    }

    if (!is_subclass_of($class, GraphQLTestCase::class)) {
        continue;
    }

    $name = $file->getFilenameWithoutExtension();

    if ($name === '') {
        continue;
    }

    $test = new $class($name);
    $reflector = new ReflectionClass($test);
    $reflector->getMethod('setUp')->invoke($test);
    $projectConfig = $test->getProjectConfig();

    if ($projectConfig->inlineProcessingDirectories !== [] || $projectConfig->twigProcessingDirectories !== []) {
        throw new LogicException(sprintf(
            '%s reads directories for every schema, which this file cannot merge. Give the directories to the schemas instead.',
            $class,
        ));
    }

    foreach ($projectConfig->schemas as $schemaConfig) {
        $config = $config->withSchema($schemaConfig);
    }
}

return $config;
