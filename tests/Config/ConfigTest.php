<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\TestClient;

final class ConfigTest extends TestCase
{
    public function testSchemaReadsTheDirectoriesOfTheConfigAndItsOwn(): void
    {
        $billing = $this
            ->createSchemaConfig('billing')
            ->withInlineProcessingDirectory('/app/src/Billing')
            ->withTwigProcessingDirectory('/app/templates/billing');

        $config = Config::create('/app')
            ->withInlineProcessingDirectory('/app/src/Checkout')
            ->withTwigProcessingDirectory('/app/templates/checkout')
            ->withSchema($billing);

        self::assertSame(['/app/src/Checkout', '/app/src/Billing'], $config->inlineProcessingDirectoriesOf($billing));
        self::assertSame(
            ['/app/templates/checkout', '/app/templates/billing'],
            $config->twigProcessingDirectoriesOf($billing),
        );
    }

    public function testFindsTheSchemasThatReadAFile(): void
    {
        $billing = $this->createSchemaConfig('billing')->withInlineProcessingDirectory('/app/src/Billing');
        $shipping = $this->createSchemaConfig('shipping')->withInlineProcessingDirectory('/app/src/Shipping/');

        $config = Config::create('/app')
            ->withInlineProcessingDirectory('/app/src/Checkout')
            ->withSchema($billing)
            ->withSchema($shipping);

        self::assertSame(
            [$billing, $shipping],
            $config->schemasReadingInlineFile('/app/src/Checkout/CheckoutController.php'),
        );
        self::assertSame(
            [$billing],
            $config->schemasReadingInlineFile('/app/src/Billing/Invoice/InvoiceController.php'),
        );
        self::assertSame(
            [$shipping],
            $config->schemasReadingInlineFile('/app/src/Shipping/../Shipping/ParcelController.php'),
        );
        self::assertSame([], $config->schemasReadingInlineFile('/app/src/BillingExtra/Controller.php'));
    }

    public function testListsEveryDirectoryItReads(): void
    {
        $config = Config::create('/app')
            ->withInlineProcessingDirectory('/app/src/Checkout')
            ->withTwigProcessingDirectory('/app/templates')
            ->withSchema($this->createSchemaConfig('billing')->withInlineProcessingDirectory('/app/src/Billing'))
            ->withSchema($this->createSchemaConfig('shipping')->withTwigProcessingDirectory('/app/templates/shipping'));

        self::assertSame(
            ['/app/src/Checkout', '/app/templates', '/app/src/Billing', '/app/templates/shipping'],
            $config->processingDirectories(),
        );
    }

    public function testOneSchemaDoesNotNeedAName(): void
    {
        $schemaConfig = $this->createSchemaConfig(null);

        self::assertSame([$schemaConfig], Config::create('/app')->withSchema($schemaConfig)->schemas);
    }

    public function testEverySchemaNeedsANameWhenThereIsMoreThanOne(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageIsOrContains(
            'The schema that generates into "App\Generated\Unnamed" has no name.',
        );

        Config::create('/app')
            ->withSchema($this->createSchemaConfig(null, 'Unnamed'))
            ->withSchema($this->createSchemaConfig('shipping'));
    }

    public function testSchemaNamesAreUnique(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageIsOrContains('There is more than one schema named "billing".');

        Config::create('/app')
            ->withSchema($this->createSchemaConfig('billing', 'One'))
            ->withSchema($this->createSchemaConfig('billing', 'Two'));
    }

    public function testSchemasGenerateIntoTheirOwnNamespace(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageIsOrContains(
            'There is more than one schema that generates into the namespace "App\Generated\Same".',
        );

        Config::create('/app')
            ->withSchema($this->createSchemaConfig('billing', 'Same'))
            ->withSchema($this->createSchemaConfig('shipping', 'Same'));
    }

    public function testSchemasGenerateIntoTheirOwnDirectory(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageIsOrContains(
            'There is more than one schema that generates into the directory "/app/generated/".',
        );

        Config::create('/app')
            ->withSchema(SchemaConfig::create(
                'type Query { a: String }',
                '/app/generated',
                'App\Billing',
                TestClient::class,
                'billing',
            ))
            ->withSchema(SchemaConfig::create(
                'type Query { a: String }',
                '/app/generated/',
                'App\Shipping',
                TestClient::class,
                'shipping',
            ));
    }

    private function createSchemaConfig(?string $name, ?string $namespace = null): SchemaConfig
    {
        $namespace ??= ucfirst($name ?? 'Default');

        return SchemaConfig::create(
            schema: 'type Query { viewer: String }',
            outputDir: '/app/generated/' . $namespace,
            namespace: 'App\\Generated\\' . $namespace,
            client: TestClient::class,
            name: $name,
        );
    }
}
