<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\Executor\PlanExecutor;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Planner;
use Ruudk\GraphQLCodeGenerator\Schemas\Billing\InvoiceController;
use Ruudk\GraphQLCodeGenerator\Schemas\Checkout\CheckoutController;
use Ruudk\GraphQLCodeGenerator\Schemas\Checkout\ShipmentController;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\CheckoutViewer0d4a41\CheckoutViewerQuery;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\Invoices68bfaf\InvoicesQuery;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\CheckoutParcelsaa940f\CheckoutParcelsQuery;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\ShipmentParcelsa15f3a\ShipmentParcelsQuery;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Symfony\Component\Filesystem\Filesystem;

final class SchemasTest extends GraphQLTestCase
{
    #[Override]
    public function getProjectConfig(): Config
    {
        return Config::create(dirname(__DIR__, 2))
            ->withSchema(SchemaConfig::create(
                schema: __DIR__ . '/Billing.graphql',
                outputDir: __DIR__ . '/Generated/Billing',
                namespace: __NAMESPACE__ . '\\Generated\\Billing',
                client: TestClient::class,
                name: 'billing',
            )->withInlineProcessingDirectory(__DIR__ . '/Checkout', __DIR__ . '/Billing'))
            ->withSchema(SchemaConfig::create(
                schema: __DIR__ . '/Shipping.graphql',
                outputDir: __DIR__ . '/Generated/Shipping',
                namespace: __NAMESPACE__ . '\\Generated\\Shipping',
                client: TestClient::class,
                name: 'shipping',
            )->withInlineProcessingDirectory(__DIR__ . '/Checkout'));
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQueriesTheClientsOfBothSchemas(): void
    {
        $controller = new CheckoutController(
            new CheckoutViewerQuery($this->getClient([
                'data' => [
                    'viewer' => [
                        'login' => 'ruudk',
                    ],
                ],
            ], new GraphQLRequestMatcher(operationName: 'CheckoutViewer'))),
            new CheckoutParcelsQuery($this->getClient([
                'data' => [
                    'parcels' => [
                        [
                            'trackingCode' => '3SABCD',
                        ],
                    ],
                ],
            ], new GraphQLRequestMatcher(operationName: 'CheckoutParcels'))),
        );

        self::assertSame(['ruudk', '3SABCD'], [
            $controller->viewer->execute()->viewer->login,
            $controller->parcels->execute()->parcels[0]->trackingCode,
        ]);
    }

    public function testQueriesTheClientsOfFilesThatOneSchemaOwns(): void
    {
        $invoices = new InvoiceController(new InvoicesQuery($this->getClient([
            'data' => [
                'bills' => [
                    [
                        'id' => 'invoice-1',
                        'total' => 2500,
                    ],
                ],
            ],
        ], new GraphQLRequestMatcher(operationName: 'Invoices'))));
        $shipment = new ShipmentController(new ShipmentParcelsQuery($this->getClient([
            'data' => [
                'parcels' => [
                    [
                        'trackingCode' => '3SABCD',
                    ],
                ],
            ],
        ], new GraphQLRequestMatcher(operationName: 'ShipmentParcels'))));

        self::assertSame([2500, '3SABCD'], [
            $invoices->invoices->execute()->bills[0]->total,
            $shipment->parcels->execute()->parcels[0]->trackingCode,
        ]);
    }

    public function testRemovesTheImportOfAClientThatMovedToAnotherSchema(): void
    {
        $filesystem = new Filesystem();
        $path = __DIR__ . '/Checkout/ShipmentController.php';
        $original = $filesystem->readFile($path);
        $billingImport = 'use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\ShipmentParcelsa15f3a\ShipmentParcelsQuery;';
        $shippingImport = 'use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\ShipmentParcelsa15f3a\ShipmentParcelsQuery;';

        try {
            // As if the client used to belong to the billing schema, which has no client left in this file.
            $filesystem->dumpFile($path, str_replace($shippingImport, $billingImport, $original));

            $config = $this->getProjectConfig();
            [$billing] = $config->schemas;
            $files = new PlanExecutor($billing)->execute(new Planner($config, $billing)->plan());

            self::assertArrayHasKey($path, $files);
            self::assertStringNotContainsString($billingImport, $files[$path]);
        } finally {
            $filesystem->dumpFile($path, $original);
        }
    }
}
