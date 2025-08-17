<?php

declare(strict_types=1);

include __DIR__ . '/../../vendor/autoload.php';

use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Symfony\Component\Finder\Finder;
use Webmozart\Assert\Assert;

foreach (Finder::create()->files()->in(dirname(__DIR__))->name('*Test.php') as $file) {
    $class = 'Ruudk\\GraphQLCodeGenerator\\' . $file->getRelativePath() . '\\' . $file->getFilenameWithoutExtension();

    if ( ! class_exists($class)) {
        continue;
    }

    if ( ! is_subclass_of($class, GraphQLTestCase::class)) {
        continue;
    }

    echo sprintf('Regenerating expected output for %s ', $class);

    $instance = new $class($file->getFilenameWithoutExtension());

    Assert::isInstanceOf($instance, GraphQLTestCase::class);

    $instance->generateExpected();

    echo "✅\n";
}
