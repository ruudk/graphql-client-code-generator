<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpDefinitions;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\DumpDefinitions\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class DumpDefinitionsTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()->enableDumpDefinition();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
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
        ]))->execute();
        self::assertSame('ruudk', $result->viewer->login);
        self::assertCount(1, $result->viewer->projects);
        [$project] = $result->viewer->projects;
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertNull($project->description);
    }
}
