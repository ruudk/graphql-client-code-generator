<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Enum\IssueState;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\Customers\Edge\Node;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\Data\IssuesByAssigneeAndState;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField\TestMultiFieldQuery;
use Ruudk\GraphQLCodeGenerator\GroupByDirective\ValueObjects\AccountId;
use Symfony\Component\TypeInfo\Type;

final class GroupByDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()
            ->enableGroupByDirective()
            ->enableAddNodesOnConnections()
            ->withScalar('AccountId', Type::int(), Type::object(AccountId::class));
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

    public function testMultiFieldQuery(): void
    {
        $issue = static fn(string $id, string $title, string $state, int $assignee) => [
            'id' => $id,
            'title' => $title,
            'state' => $state,
            'assignee' => [
                'id' => $assignee,
            ],
        ];
        $customer = static fn(int $id, string $name, string $tier, string $country) => [
            'node' => [
                'id' => $id,
                'name' => $name,
                'tier' => $tier,
                'country' => $country,
            ],
        ];

        $result = new TestMultiFieldQuery($this->getClient([
            'data' => [
                'issuesByAssigneeAndState' => [
                    $issue('1', 'First', 'CLOSED', 20),
                    $issue('2', 'Second', 'OPEN', 10),
                    $issue('3', 'Third', 'OPEN', 20),
                    $issue('4', 'Fourth', 'CLOSED', 20),
                    $issue('5', 'Fifth', 'OPEN', 20),
                ],
                'customers' => [
                    'edges' => [
                        $customer(1, 'Alice', 'SILVER', 'NL'),
                        $customer(2, 'Bob', 'GOLD', 'BE'),
                        $customer(3, 'Carol', 'SILVER', 'BE'),
                        $customer(4, 'Dave', 'SILVER', 'NL'),
                        $customer(5, 'Eve', 'GOLD', 'BE'),
                    ],
                ],
            ],
        ]))->execute();

        $titles = static fn(array $issues) => array_map(
            static fn(IssuesByAssigneeAndState $issue) => $issue->title,
            $issues,
        );

        // Every level keeps the order in which its keys first appear, items keep their order within a group
        $issues = $result->issuesByAssigneeAndState;
        self::assertSame([20, 10], array_keys($issues));
        self::assertSame(['CLOSED', 'OPEN'], array_keys($issues[20]));
        self::assertSame(['First', 'Fourth'], $titles($issues[20]['CLOSED']));
        self::assertSame(['Third', 'Fifth'], $titles($issues[20]['OPEN']));
        self::assertSame(['OPEN'], array_keys($issues[10]));
        self::assertSame(['Second'], $titles($issues[10]['OPEN']));
        self::assertSame(IssueState::Closed, $issues[20]['CLOSED'][0]->state);
        self::assertEquals(new AccountId(20), $issues[20]['CLOSED'][0]->assignee->id);

        // Grouped edges give nodes in the same nested groups
        $edges = $result->customers->edges;
        self::assertSame(['SILVER', 'GOLD'], array_keys($edges));
        self::assertSame(['NL', 'BE'], array_keys($edges['SILVER']));
        self::assertSame(['Alice', 'Dave'], array_map(static fn($edge) => $edge->node->name, $edges['SILVER']['NL']));

        $names = static fn(array $nodes) => array_map(static fn(Node $node) => $node->name, $nodes);
        $nodes = $result->customers->nodes;
        self::assertSame(['SILVER', 'GOLD'], array_keys($nodes));
        self::assertSame(['NL', 'BE'], array_keys($nodes['SILVER']));
        self::assertSame(['Alice', 'Dave'], $names($nodes['SILVER']['NL']));
        self::assertSame(['Carol'], $names($nodes['SILVER']['BE']));
        self::assertSame(['BE'], array_keys($nodes['GOLD']));
        self::assertSame(['Bob', 'Eve'], $names($nodes['GOLD']['BE']));
    }

    public function testMultiFieldEmptyList(): void
    {
        $result = new TestMultiFieldQuery($this->getClient([
            'data' => [
                'issuesByAssigneeAndState' => [],
                'customers' => [
                    'edges' => [],
                ],
            ],
        ]))->execute();

        self::assertSame([], $result->issuesByAssigneeAndState);
        self::assertSame([], $result->customers->edges);
        self::assertSame([], $result->customers->nodes);
    }
}
