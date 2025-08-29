<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments;

use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class FragmentsTest extends GraphQLTestCase
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
                        'description' => null,
                        'state' => 'ACTIVE',
                    ],
                ],
            ],
        ]))->execute();

        self::assertNotNull($result->viewer->viewerName);
        self::assertTrue($result->viewer->isViewerName);
        self::assertSame('Ruud Kamphuis', $result->viewer->viewerName->name);
        self::assertTrue($result->viewer->isUserDetails);
        self::assertSame('ruudk', $result->viewer->userDetails?->login);
        self::assertNull($result->viewer->applicationDetails);
        self::assertFalse($result->viewer->isApplicationDetails);
        self::assertCount(1, $result->projects);
        [$project] = $result->projects;
        self::assertSame('GraphQL Code Generator', $project->projectView->name);
        self::assertSame('ACTIVE', $project->projectView->projectStateView->state);
        self::assertNull($project->projectView->description);

        self::assertStringContainsString('fragment ProjectStateView on Project', $this->getLastOperation());
        self::assertStringContainsString('fragment ProjectView on Project', $this->getLastOperation());
        self::assertStringContainsString('fragment ViewerName on Viewer', $this->getLastOperation());
        self::assertStringContainsString('fragment ApplicationDetails on Application', $this->getLastOperation());
        self::assertStringContainsString('fragment UserDetails on User', $this->getLastOperation());
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
        self::assertNotNull($result->viewer->viewerName);
        self::assertTrue($result->viewer->isViewerName);
        self::assertSame('Application', $result->viewer->viewerName->name);
        self::assertFalse($result->viewer->isUserDetails);
        self::assertNull($result->viewer->userDetails);
        self::assertNotNull($result->viewer->applicationDetails);
        self::assertTrue($result->viewer->isApplicationDetails);
        self::assertSame('https://example', $result->viewer->applicationDetails->url);
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
        self::assertFalse($result->viewer->isViewerName);
        self::assertNull($result->viewer->viewerName);
    }
}
