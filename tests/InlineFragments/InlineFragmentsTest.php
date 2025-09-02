<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments;

use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\TestQuery;

final class InlineFragmentsTest extends GraphQLTestCase
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
        self::assertTrue($result->viewer->isUser);
        self::assertSame('ruudk', $result->viewer->asUser?->login);
        self::assertFalse($result->viewer->isApplication);
        self::assertNull($result->viewer->asApplication);
        self::assertCount(1, $result->projects);
        [$project] = $result->projects;
        self::assertSame('GraphQL Code Generator', $project->name);
        self::assertSame('Hello, World!', $project->description);
    }

    public function testApplicationViewer() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'Application',
                    'name' => 'Application',
                    'url' => 'https://example',
                ],
                'projects' => [],
            ],
        ]))->execute();
        self::assertSame('Application', $result->viewer->name);
        self::assertFalse($result->viewer->isUser);
        self::assertNull($result->viewer->asUser);
        self::assertTrue($result->viewer->isApplication);
        self::assertNotNull($result->viewer->asApplication);
        self::assertSame('https://example', $result->viewer->asApplication->url);
    }

    public function testNullWhenViewerIsDifferentType() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'Unexpected',
                    'login' => 'ruudk',
                ],
                'projects' => [],
            ],
        ]))->execute();
        self::assertFalse($result->viewer->isUser);
        self::assertNull($result->viewer->asUser);
    }
}
