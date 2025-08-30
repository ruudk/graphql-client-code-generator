<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpOrThrows;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\DumpOrThrows\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class DumpOrThrowsTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()->enableDumpOrThrows();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testOrThrowsMethodReturnsData() : void
    {
        $query = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'login' => 'ruudk',
                    'projects' => [
                        [
                            'name' => 'GraphQL Code Generator',
                            'description' => 'A powerful code generator',
                        ],
                    ],
                ],
            ],
        ]));
        // Test that executeOrThrow() returns the data when no errors
        $data = $query->executeOrThrow();
        self::assertSame('ruudk', $data->viewer->login);
        self::assertCount(1, $data->viewer->projects);
    }

    public function testOrThrowsMethodThrowsException() : void
    {
        $query = new TestQuery($this->getClient([
            'errors' => [
                [
                    'message' => 'Something went wrong',
                    'code' => 'INTERNAL_ERROR',
                    'path' => ['viewer'],
                ],
            ],
            'data' => [],
        ]));
        // Test that executeOrThrow() throws exception when there are errors
        $this->expectException(Generated\Query\Test\TestQueryFailedException::class);
        $this->expectExceptionMessage('Something went wrong');
        $query->executeOrThrow();
    }

    public function testNodeOrThrows() : void
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
        // Test that we can access the viewer node
        $viewer = $result->viewer;
        self::assertSame('ruudk', $viewer->login);
        // Test accessing projects
        $project = $viewer->projects[0];
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertNull($project->description);
    }
}
