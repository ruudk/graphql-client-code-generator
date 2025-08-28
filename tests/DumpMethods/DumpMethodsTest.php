<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpMethods;

use Override;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Enum\ProjectStatus;
use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Enum\UserRole;
use Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class DumpMethodsTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableDumpMethods()
            ->enableAddNodesOnConnections()
            ->enableUseNodeNameForEdgeNodes()
            ->enableUseConnectionNameForConnections()
            ->enableUseEdgeNameForEdges();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testEnumIsAndCreateMethods() : void
    {
        // Test enum create methods
        $adminRole = UserRole::createAdmin();
        self::assertSame(UserRole::Admin, $adminRole);
        $activeStatus = ProjectStatus::createActive();
        self::assertSame(ProjectStatus::Active, $activeStatus);
        // Test enum is methods
        self::assertTrue($adminRole->isAdmin());
        self::assertFalse($adminRole->isUser());
        self::assertFalse($adminRole->isGuest());
        self::assertTrue($activeStatus->isActive());
        self::assertFalse($activeStatus->isDraft());
        self::assertFalse($activeStatus->isArchived());
    }

    public function testDataClassGetterMethods() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    'id' => '123',
                    'name' => 'John Doe',
                    'role' => 'ADMIN',
                    'createdAt' => '2024-01-01T00:00:00Z',
                ],
                'projects' => [
                    'edges' => [
                        [
                            'cursor' => 'cursor1',
                            'node' => [
                                'id' => 'proj1',
                                'name' => 'Project One',
                                'status' => 'ACTIVE',
                            ],
                        ],
                        [
                            'cursor' => 'cursor2',
                            'node' => [
                                'id' => 'proj2',
                                'name' => 'Project Two',
                                'status' => 'DRAFT',
                            ],
                        ],
                    ],
                    'pageInfo' => [
                        'hasNextPage' => true,
                        'hasPreviousPage' => false,
                        'startCursor' => 'cursor1',
                        'endCursor' => 'cursor2',
                    ],
                ],
            ],
        ]))->execute();
        // Test viewer getter methods
        $viewer = $result->getViewer();
        self::assertNotNull($viewer);
        self::assertSame('123', $viewer->id);
        self::assertSame('John Doe', $viewer->name);
        self::assertSame(UserRole::Admin, $viewer->role);
        // Test viewer role enum methods
        self::assertTrue($viewer->role->isAdmin());
        self::assertFalse($viewer->role->isUser());
        // Test projects getter methods
        $projects = $result->getProjects();
        self::assertNotNull($projects);
        // Test edges getter
        $edges = $projects->getEdges();
        self::assertCount(2, $edges);
        // Test nodes helper method (computed from edges)
        $nodes = $projects->getNodes();
        self::assertCount(2, $nodes);
        self::assertSame('proj1', $nodes[0]->id);
        self::assertSame('Project One', $nodes[0]->name);
        self::assertSame(ProjectStatus::Active, $nodes[0]->status);
        // Test project status enum methods
        self::assertTrue($nodes[0]->status->isActive());
        self::assertFalse($nodes[0]->status->isDraft());
        self::assertFalse($nodes[1]->status->isActive());
        self::assertTrue($nodes[1]->status->isDraft());
        // Test pageInfo getter
        $pageInfo = $projects->getPageInfo();
        self::assertTrue($pageInfo->hasNextPage);
        self::assertFalse($pageInfo->hasPreviousPage);
        self::assertSame('cursor1', $pageInfo->startCursor);
        self::assertSame('cursor2', $pageInfo->endCursor);
    }

    public function testErrorsGetterMethod() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => null,
                'projects' => null,
            ],
            'errors' => [
                [
                    'message' => 'Something went wrong',
                ],
            ],
        ]))->execute();
        // Test errors getter method
        $errors = $result->getErrors();
        self::assertCount(1, $errors);
        self::assertSame('Something went wrong', $errors[0]->message);
    }
}
