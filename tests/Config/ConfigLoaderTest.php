<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use PHPUnit\Framework\TestCase;

final class ConfigLoaderTest extends TestCase
{
    public function testLoadsAConfigPerFile(): void
    {
        $configs = ConfigLoader::load(__DIR__ . '/Fixtures/billing.php');

        self::assertCount(1, $configs);
        self::assertSame('/app', $configs[0]->projectDir);
    }

    public function testConfigsCannotReadTheSameDirectories(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageIsOrContains(sprintf(
            'The configs in %s and %s both read "/app/src/Checkout". Add the schemas that read the same directories to one config.',
            __DIR__ . '/Fixtures/billing.php',
            __DIR__ . '/Fixtures/shipping.php',
        ));

        ConfigLoader::load(__DIR__ . '/Fixtures/billing.php', __DIR__ . '/Fixtures/shipping.php');
    }
}
