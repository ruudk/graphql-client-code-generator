<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\Inline1d8480Query;

final class InlineProcessingTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableGeneratedFromAttribute()
            ->withInlineProcessingDirectory(__DIR__);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $query = new Inline1d8480Query($this->getClient([
            'data' => [
                'viewer' => [
                    'login' => 'ruudk',
                    'projects' => [
                        [
                            'name' => 'GraphQL Code Generator',
                            'description' => null,
                        ],
                    ],
                ],
            ],
        ]));

        $result = new SomeController($query)->getResult();

        self::assertSame(['ruudk', 'GraphQL Code Generator'], $result);
    }
}
