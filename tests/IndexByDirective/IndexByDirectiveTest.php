<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Enum\LabelStatus;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestFragments\TestFragmentsQuery;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestKeyTypes\TestKeyTypesQuery;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField\TestMultiFieldQuery;
use Ruudk\GraphQLCodeGenerator\IndexByDirective\ValueObjects\LabelCode;
use Symfony\Component\TypeInfo\Type;

final class IndexByDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()
            ->enableIndexByDirective()
            ->enableAddNodesOnConnections()
            ->withScalar('IssueId', Type::int())
            ->withScalar('LabelCode', Type::string(), Type::object(LabelCode::class))
            ->enableUseNodeNameForEdgeNodes()
            ->enableUseConnectionNameForConnections()
            ->enableUseEdgeNameForEdges();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery(): void
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

    public function testMultiFieldIndexBy(): void
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
        self::assertSame(
            'GraphQL Code Generator',
            $result->projects['16b6f807-689d-4d3f-9c9b-a1c774eed7ea']['GraphQL Code Generator']->name,
        );

        self::assertArrayHasKey('644e7023-6e09-4e18-9ef2-947de0d0ed82', $result->projects);
        self::assertArrayHasKey('GraphQL PHP', $result->projects['644e7023-6e09-4e18-9ef2-947de0d0ed82']);
        self::assertSame('GraphQL PHP', $result->projects['644e7023-6e09-4e18-9ef2-947de0d0ed82']['GraphQL PHP']->name);

        self::assertArrayHasKey('a1b2c3d4-e5f6-7890-1234-567890abcdef', $result->projects);
        self::assertArrayHasKey('GraphQL Code Generator', $result->projects['a1b2c3d4-e5f6-7890-1234-567890abcdef']);
        self::assertSame(
            'GraphQL Code Generator',
            $result->projects['a1b2c3d4-e5f6-7890-1234-567890abcdef']['GraphQL Code Generator']->name,
        );

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

    public function testIndexByEnumAndCustomScalar(): void
    {
        $result = new TestKeyTypesQuery($this->getClient([
            'data' => [
                'labelsByStatus' => [
                    [
                        'status' => 'OPEN',
                        'name' => 'Open label',
                    ],
                    [
                        'status' => 'CLOSED',
                        'name' => 'Closed label',
                    ],
                ],
                'labelsByCode' => [
                    [
                        'code' => 'bug',
                        'name' => 'Bug',
                    ],
                    [
                        'code' => 'feature',
                        'name' => 'Feature',
                    ],
                ],
            ],
        ], new GraphQLRequestMatcher(operationName: 'TestKeyTypes')))->execute();

        // Enum fields are keyed by their raw string value, the value itself is still the enum
        self::assertSame(['OPEN', 'CLOSED'], array_keys($result->labelsByStatus));
        self::assertSame(LabelStatus::Closed, $result->labelsByStatus['CLOSED']->status);
        self::assertSame('Closed label', $result->labelsByStatus['CLOSED']->name);

        // Custom scalars are keyed by their raw JSON value, the value itself is still the PHP type
        self::assertSame(['bug', 'feature'], array_keys($result->labelsByCode));
        self::assertEquals(new LabelCode('feature'), $result->labelsByCode['feature']->code);
        self::assertSame('Feature', $result->labelsByCode['feature']->name);
    }

    public function testIndexByFieldSelectedThroughFragments(): void
    {
        $result = new TestFragmentsQuery($this->getClient([
            'data' => [
                'projects' => [
                    [
                        'id' => 'project-1',
                        'name' => 'Project 1',
                    ],
                ],
                'issues' => [
                    [
                        'id' => 222,
                        'name' => 'Issue 222',
                    ],
                ],
            ],
        ], new GraphQLRequestMatcher(operationName: 'TestFragments')))->execute();

        self::assertSame(['project-1'], array_keys($result->projects));
        self::assertSame([222], array_keys($result->issues));
        self::assertSame('Issue 222', $result->issues[222]->name);
    }
}
