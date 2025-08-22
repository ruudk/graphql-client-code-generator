<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization;

use Ruudk\GraphQLCodeGenerator\GraphQLCodeGenerator;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Optimization\Expected\Query\TestQuery;

final class OptimizationTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        new GraphQLCodeGenerator($this->getConfig())->generate();

        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'User',
                    'id' => '123',
                    'idAlias' => '123',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                ],
                'projects' => [
                    [
                        'name' => 'GraphQL Code Generator',
                        'description' => 'Hello, World!',
                        'state' => 'ACTIVE',
                    ],
                ],
            ],
        ]))->execute();

        self::assertSame('Ruud Kamphuis', $result->viewer->name);
        self::assertSame('123', $result->viewer->id);
        self::assertSame('123', $result->viewer->idAlias);

        self::assertTrue($result->viewer->isUser);
        self::assertSame('ruudk', $result->viewer->asUser?->login);
        self::assertSame('Ruud Kamphuis', $result->viewer->asUser->name);

        self::assertNull($result->viewer->appUrl?->url);
        self::assertNull($result->viewer->appUrl?->appName->name);

        self::assertCount(1, $result->projects);

        [$project] = $result->projects;
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertSame('Hello, World!', $project->description);
    }
}
