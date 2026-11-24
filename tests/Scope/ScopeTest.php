<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Scope;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Scope\Generated\Query\BillingViewerece69a\BillingViewerQuery;

final class ScopeTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withScope('billing')
            ->withInlineProcessingDirectory(__DIR__);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        self::assertSame('ruudk', new BillingController(new BillingViewerQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'login' => 'ruudk',
                ],
            ],
        ])))->getLogin());
    }
}
