<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Enum\IssueState;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\TestQuery;

final class GroupByDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()->enableGroupByDirective()->enableAddNodesOnConnections();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery(): void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'issuesByState' => [
                    [
                        'id' => '1',
                        'title' => 'First',
                        'state' => 'OPEN',
                    ],
                    [
                        'id' => '2',
                        'title' => 'Second',
                        'state' => 'CLOSED',
                    ],
                    [
                        'id' => '3',
                        'title' => 'Third',
                        'state' => 'OPEN',
                    ],
                ],
                'issuesByProject' => [
                    [
                        'id' => '1',
                        'title' => 'First',
                        'project' => [
                            'id' => 'p1',
                            'name' => 'Project 1',
                        ],
                    ],
                    [
                        'id' => '2',
                        'title' => 'Second',
                        'project' => [
                            'id' => 'p2',
                            'name' => 'Project 2',
                        ],
                    ],
                    [
                        'id' => '3',
                        'title' => 'Third',
                        'project' => [
                            'id' => 'p1',
                            'name' => 'Project 1',
                        ],
                    ],
                ],
                'customers' => [
                    'edges' => [
                        [
                            'node' => [
                                'id' => 100,
                                'name' => 'Alice',
                                'country' => 'NL',
                            ],
                        ],
                        [
                            'node' => [
                                'id' => 200,
                                'name' => 'Bob',
                                'country' => 'BE',
                            ],
                        ],
                        [
                            'node' => [
                                'id' => 300,
                                'name' => 'Carol',
                                'country' => 'NL',
                            ],
                        ],
                    ],
                ],
            ],
        ]))->execute();

        // Groups keep the order in which their key first appears, items keep their order within a group
        self::assertSame(['OPEN', 'CLOSED'], array_keys($result->issuesByState));
        self::assertSame(
            ['First', 'Third'],
            array_map(static fn($issue) => $issue->title, $result->issuesByState['OPEN']),
        );
        self::assertSame(IssueState::Open, $result->issuesByState['OPEN'][0]->state);
        self::assertSame(['Second'], array_map(static fn($issue) => $issue->title, $result->issuesByState['CLOSED']));

        // Nested field paths
        self::assertSame(['p1', 'p2'], array_keys($result->issuesByProject));
        self::assertSame(
            ['First', 'Third'],
            array_map(static fn($issue) => $issue->title, $result->issuesByProject['p1']),
        );
        self::assertSame('Project 2', $result->issuesByProject['p2'][0]->project->name);

        // Grouped edges give nodes in the same groups
        self::assertSame(['NL', 'BE'], array_keys($result->customers->edges));
        self::assertSame(
            ['Alice', 'Carol'],
            array_map(static fn($edge) => $edge->node->name, $result->customers->edges['NL']),
        );
        self::assertSame(['NL', 'BE'], array_keys($result->customers->nodes));
        self::assertSame(
            ['Alice', 'Carol'],
            array_map(static fn($node) => $node->name, $result->customers->nodes['NL']),
        );
        self::assertSame(['Bob'], array_map(static fn($node) => $node->name, $result->customers->nodes['BE']));
    }

    public function testEmptyList(): void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'issuesByState' => [],
                'issuesByProject' => [],
                'customers' => [
                    'edges' => [],
                ],
            ],
        ]))->execute();

        self::assertSame([], $result->issuesByState);
        self::assertSame([], $result->issuesByProject);
        self::assertSame([], $result->customers->edges);
        self::assertSame([], $result->customers->nodes);
    }
}
