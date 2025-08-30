<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NullableConnections;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\TestQuery;

final class NullableConnectionsTest extends GraphQLTestCase
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
                'projects' => [
                    'edges' => [
                        [
                            'cursor' => 'cursor1',
                            'node' => [
                                'id' => 'proj1',
                                'name' => 'Project One',
                            ],
                        ],
                        null,
                        [
                            'cursor' => 'cursor2',
                            'node' => [
                                'id' => 'proj2',
                                'name' => 'Project Two',
                            ],
                        ],
                    ],
                ],
            ],
        ]))->execute();
        $projects = $result->projects;
        self::assertNotNull($projects);
        $edges = $projects->edges;
        self::assertNotNull($edges);
        self::assertCount(3, $edges);
        [$edge1, $edge2, $edge3] = $edges;
        self::assertNotNull($edge1);
        self::assertSame('cursor1', $edge1->cursor);
        self::assertSame('proj1', $edge1->node->id);
        self::assertSame('Project One', $edge1->node->name);
        self::assertNull($edge2);
        self::assertNotNull($edge3);
        self::assertSame('cursor2', $edge3->cursor);
        self::assertSame('proj2', $edge3->node->id);
        self::assertSame('Project Two', $edge3->node->name);
    }
}
