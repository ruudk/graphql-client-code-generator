<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Simple;

use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Simple\Generated\Query\TestQuery;

final class SimpleTest extends GraphQLTestCase
{
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
