<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\TestClient;

final class ConfigTest extends TestCase
{
    public function testHasNoScopeByDefault() : void
    {
        self::assertNull($this->createConfig()->scope);
    }

    public function testTakesAStringAsScope() : void
    {
        self::assertSame('billing', $this->createConfig()->withScope('billing')->scope);
    }

    private function createConfig() : Config
    {
        return Config::create(
            schema: 'type Query { viewer: String }',
            projectDir: __DIR__,
            outputDir: __DIR__ . '/Generated',
            namespace: 'App\\Generated',
            client: TestClient::class,
        );
    }
}
