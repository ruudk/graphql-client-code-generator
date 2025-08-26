<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ConnectionNames;

use Override;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\TestQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class ConnectionNamesTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableUseNodeNameForEdgeNodes()
            ->enableUseConnectionNameForConnections()
            ->enableUseEdgeNameForEdges();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testConnectionNaming() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => null,
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
                        'hasNextPage' => false,
                        'hasPreviousPage' => false,
                    ],
                ],
            ],
        ]))->execute();
        // Test that connection is properly named with Connection suffix
        $projects = $result->projects;
        self::assertNotNull($projects);
        // Test that edges are properly accessible
        $edges = $projects->edges;
        self::assertCount(2, $edges);
        // Test edge naming (should use Edge suffix)
        $firstEdge = $edges[0];
        self::assertSame('cursor1', $firstEdge->cursor);
        self::assertSame('proj1', $firstEdge->node->id);
        self::assertSame('Project One', $firstEdge->node->name);
        // Test that pageInfo is accessible
        $pageInfo = $projects->pageInfo;
        self::assertFalse($pageInfo->hasNextPage);
        self::assertFalse($pageInfo->hasPreviousPage);
    }
}
