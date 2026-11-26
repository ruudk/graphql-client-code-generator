<?php

declare(strict_types=1);

/*
 * PHPStan ships as a PHAR, so Mago cannot see its classes. Extract its sources
 * into the vendor directory so Mago can analyze the PHPStan extension.
 */

$root = dirname(__DIR__);
$phar = new Phar($root . '/vendor/phpstan/phpstan/phpstan.phar');
$prefix = 'phar://' . $phar->getPath() . '/';
$files = [];

foreach (new RecursiveIteratorIterator($phar) as $file) {
    if (!$file instanceof PharFileInfo) {
        continue;
    }

    $name = substr($file->getPathname(), strlen($prefix));

    if (str_starts_with($name, 'src/')) {
        $files[] = $name;
    }
}

$phar->extractTo($root . '/vendor/phpstan/phpstan/phar', $files, true);
