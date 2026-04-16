<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHPStan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RestrictedUsageExtension::class)]
final class RestrictedUsageExtensionTest extends TestCase
{
    public function testExtension() : void
    {
        $command = sprintf(
            '%s analyse --configuration=%s --no-progress --error-format=raw 2>&1',
            escapeshellarg(dirname(__DIR__, 2) . '/vendor/bin/phpstan'),
            escapeshellarg(__DIR__ . '/phpstan.neon'),
        );

        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, sprintf(
            "PHPStan reported errors for tests/PHPStan fixtures.\n\nOutput:\n%s",
            implode(PHP_EOL, $output),
        ));
    }
}
