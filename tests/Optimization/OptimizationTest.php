<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization;

use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\TestQuery;

final class OptimizationTest extends GraphQLTestCase
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
        // Test Viewer properties
        self::assertObjectHasProperty('id', $result->viewer, 'Viewer should have id property');
        self::assertObjectHasProperty('idAlias', $result->viewer, 'Viewer should have idAlias property');
        self::assertObjectHasProperty('name', $result->viewer, 'Viewer should have name property');
        self::assertObjectHasProperty('asUser', $result->viewer, 'Viewer should have asUser property');
        self::assertObjectHasProperty('appUrl', $result->viewer, 'Viewer should have appUrl fragment property');
        // Viewer should NOT have direct access to type-specific fields
        self::assertObjectNotHasProperty('login', $result->viewer, 'Viewer should NOT have direct login property');
        self::assertObjectNotHasProperty('url', $result->viewer, 'Viewer should NOT have direct url property');
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
        // Test Project properties - all fields should be directly accessible (from merged inline fragments)
        self::assertObjectHasProperty('name', $project, 'Project should have name property');
        self::assertObjectHasProperty('description', $project, 'Project should have description property');
        self::assertObjectHasProperty('state', $project, 'Project should have state property');
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertSame('Hello, World!', $project->description);
        self::assertSame('ACTIVE', $project->state);
    }
}
