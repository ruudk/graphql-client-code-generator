<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScopeShipping;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Scope\CheckoutController;
use Ruudk\GraphQLCodeGenerator\Scope\Generated\Query\CheckoutViewer59d12c\CheckoutViewerQuery;
use Ruudk\GraphQLCodeGenerator\ScopeShipping\Generated\Query\CheckoutParcels40ab14\CheckoutParcelsQuery;

final class ScopeShippingTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withScope('shipping')
            ->withInlineProcessingDirectory(dirname(__DIR__) . '/Scope');
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQueriesTheClientsOfBothConfigs() : void
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

        self::assertSame(
            ['ruudk', '3SABCD'],
            [
                $controller->viewer->execute()->viewer->login,
                $controller->parcels->execute()->parcels[0]->trackingCode,
            ],
        );
    }
}
