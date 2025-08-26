<?php

declare(strict_types=1);
include __DIR__ . '/../vendor/autoload.php';
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Symfony\Component\Finder\Finder;

$config = [];
foreach (Finder::create()->files()->depth(1)->in(__DIR__)->name('*Test.php') as $file) {
    $class = 'Ruudk\\GraphQLCodeGenerator\\' . $file->getRelativePath() . '\\' . $file->getFilenameWithoutExtension();

    if ( ! class_exists($class)) {
        continue;
    }

    if ( ! is_subclass_of($class, GraphQLTestCase::class)) {
        continue;
    }

    $test = new $class($file->getFilenameWithoutExtension());
    $reflector = new ReflectionClass($test);
    $reflector->getMethod('setUp')->invoke($test);
    $config[] = $test->getConfig();
}

return $config;
