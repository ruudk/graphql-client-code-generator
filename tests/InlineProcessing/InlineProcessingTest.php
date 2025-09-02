<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\ViewerProjects1d8480\ViewerProjectsQuery;

final class InlineProcessingTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableGeneratedAttribute()
            ->enableDumpOrThrows()
            ->withInlineProcessingDirectory(__DIR__);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $query = new ViewerProjectsQuery($this->getClient([
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
