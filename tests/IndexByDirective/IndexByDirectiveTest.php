<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiFieldQuery;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestQuery;
use Symfony\Component\TypeInfo\Type;

final class IndexByDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableIndexByDirective()
            ->enableAddNodesOnConnections()
            ->withScalar('IssueId', Type::int())
            ->enableUseNodeNameForEdgeNodes()
            ->enableUseConnectionNameForConnections()
            ->enableUseEdgeNameForEdges();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'projects' => [
                    [
                        'id' => '16b6f807-689d-4d3f-9c9b-a1c774eed7ea',
                        'name' => 'GraphQL Code Generator',
                    ],
                    [
                        'id' => '644e7023-6e09-4e18-9ef2-947de0d0ed82',
                        'name' => 'GraphQL PHP',
                    ],
                ],
                'issues' => [
                    [
                        'id' => 222,
                        'name' => 'Issue 222',
                    ],
                    [
                        'id' => 444,
                        'name' => 'Issue 444',
                    ],
                ],
                'customers' => [
                    'edges' => [
                        [
                            'node' => [
                                'id' => 100,
                                'name' => 'Customer 100',
                            ],
                        ],
                        [
                            'node' => [
                                'id' => 200,
                                'name' => 'Customer 200',
                            ],
                        ],
                    ],
                ],
            ],
        ]))->execute();
        self::assertCount(2, $result->projects);
        self::assertArrayHasKey('16b6f807-689d-4d3f-9c9b-a1c774eed7ea', $result->projects);
        self::assertSame('GraphQL Code Generator', $result->projects['16b6f807-689d-4d3f-9c9b-a1c774eed7ea']->name);
        self::assertArrayHasKey('644e7023-6e09-4e18-9ef2-947de0d0ed82', $result->projects);
        self::assertSame('GraphQL PHP', $result->projects['644e7023-6e09-4e18-9ef2-947de0d0ed82']->name);
        self::assertCount(2, $result->issues);
        self::assertArrayHasKey(222, $result->issues);
        self::assertSame('Issue 222', $result->issues[222]->name);
        self::assertArrayHasKey(444, $result->issues);
        self::assertSame('Issue 444', $result->issues[444]->name);
        self::assertCount(2, $result->customers->edges);
        self::assertArrayHasKey(100, $result->customers->edges);
        self::assertSame('Customer 100', $result->customers->edges[100]->node->name);
        self::assertArrayHasKey(200, $result->customers->edges);
        self::assertSame('Customer 200', $result->customers->edges[200]->node->name);
        self::assertCount(2, $result->customers->nodes);
        self::assertArrayHasKey(100, $result->customers->nodes);
        self::assertSame('Customer 100', $result->customers->nodes[100]->name);
        self::assertArrayHasKey(200, $result->customers->nodes);
        self::assertSame('Customer 200', $result->customers->nodes[200]->name);
    }

    public function testMultiFieldIndexBy() : void
    {
        $result = new TestMultiFieldQuery($this->getClient([
            'data' => [
                'projects' => [
                    [
                        'id' => '16b6f807-689d-4d3f-9c9b-a1c774eed7ea',
                        'name' => 'GraphQL Code Generator',
                    ],
                    [
                        'id' => '644e7023-6e09-4e18-9ef2-947de0d0ed82',
                        'name' => 'GraphQL PHP',
                    ],
                    [
                        'id' => 'a1b2c3d4-e5f6-7890-1234-567890abcdef',
                        'name' => 'GraphQL Code Generator',
                    ],
                ],
                'issues' => [
                    [
                        'id' => 222,
                        'name' => 'Issue 222',
                    ],
                    [
                        'id' => 444,
                        'name' => 'Issue 444',
                    ],
                ],
                'customers' => [
                    'edges' => [
                        [
                            'node' => [
                                'id' => 100,
                                'name' => 'Customer Alpha',
                            ],
                        ],
                        [
                            'node' => [
                                'id' => 200,
                                'name' => 'Customer Beta',
                            ],
                        ],
                        [
                            'node' => [
                                'id' => 100,
                                'name' => 'Customer Gamma',
                            ],
                        ],
                    ],
                ],
            ],
        ], new GraphQLRequestMatcher(operationName: 'TestMultiField')))->execute();

        // Test multi-field indexing: projects indexed by id,name should create nested arrays
        self::assertCount(3, $result->projects);
        self::assertArrayHasKey('16b6f807-689d-4d3f-9c9b-a1c774eed7ea', $result->projects);
        self::assertArrayHasKey('GraphQL Code Generator', $result->projects['16b6f807-689d-4d3f-9c9b-a1c774eed7ea']);
        self::assertSame('GraphQL Code Generator', $result->projects['16b6f807-689d-4d3f-9c9b-a1c774eed7ea']['GraphQL Code Generator']->name);

        self::assertArrayHasKey('644e7023-6e09-4e18-9ef2-947de0d0ed82', $result->projects);
        self::assertArrayHasKey('GraphQL PHP', $result->projects['644e7023-6e09-4e18-9ef2-947de0d0ed82']);
        self::assertSame('GraphQL PHP', $result->projects['644e7023-6e09-4e18-9ef2-947de0d0ed82']['GraphQL PHP']->name);

        self::assertArrayHasKey('a1b2c3d4-e5f6-7890-1234-567890abcdef', $result->projects);
        self::assertArrayHasKey('GraphQL Code Generator', $result->projects['a1b2c3d4-e5f6-7890-1234-567890abcdef']);
        self::assertSame('GraphQL Code Generator', $result->projects['a1b2c3d4-e5f6-7890-1234-567890abcdef']['GraphQL Code Generator']->name);

        // Test multi-field indexing on edges: edges indexed by node.id,node.name
        self::assertCount(2, $result->customers->edges);
        self::assertArrayHasKey(100, $result->customers->edges);
        self::assertArrayHasKey('Customer Alpha', $result->customers->edges[100]);
        self::assertSame('Customer Alpha', $result->customers->edges[100]['Customer Alpha']->node->name);
        self::assertArrayHasKey('Customer Gamma', $result->customers->edges[100]);
        self::assertSame('Customer Gamma', $result->customers->edges[100]['Customer Gamma']->node->name);

        self::assertArrayHasKey(200, $result->customers->edges);
        self::assertArrayHasKey('Customer Beta', $result->customers->edges[200]);
        self::assertSame('Customer Beta', $result->customers->edges[200]['Customer Beta']->node->name);
    }
}
