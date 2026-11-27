<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\List\ListQuery;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes\ListTicketTypesQuery;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\Nullable\NullableQuery;

final class NarrowDirectiveTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()->enableNarrowDirective()->enableThrowWhenNullDirective();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testNarrowedField(): void
    {
        $result = new ListTicketTypesQuery($this->getClient([
            'data' => [
                'event' => [
                    '__typename' => 'Event',
                    'id' => 'RXZlbnQ6MQ==',
                    'name' => 'Awakenings',
                    'types' => [
                        'edges' => [
                            [
                                'cursor' => 'YXJyYXljb25uZWN0aW9uOjA=',
                                'node' => [
                                    'id' => 'RXZlbnRUeXBlOjE=',
                                    'title' => 'Weekend ticket',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]))->execute('RXZlbnQ6MQ==', 10);

        self::assertSame('Event', $result->event->__typename);
        self::assertSame('Awakenings', $result->event->name);
        self::assertSame('Weekend ticket', $result->event->types->edges[0]->node->title);
        self::assertStringNotContainsString('@narrow', $this->getLastOperation());
    }

    public function testNarrowedFieldThrowsOnOtherType(): void
    {
        $result = new ListTicketTypesQuery($this->getClient([
            'data' => [
                'event' => [
                    '__typename' => 'User',
                    'id' => 'VXNlcjox',
                ],
            ],
        ]))->execute('VXNlcjox', 10);

        self::assertThrowsNodeNotFound(
            'Field Query.event is User, expected Event',
            static fn() => $result->event->name,
        );
    }

    public function testNarrowedFieldThrowsWhenNull(): void
    {
        $result = new ListTicketTypesQuery($this->getClient([
            'data' => [
                'event' => null,
            ],
        ]))->execute('RXZlbnQ6MQ==', 10);

        self::assertThrowsNodeNotFound('Field Query.event is null', static fn() => $result->event->name);
    }

    public function testNullable(): void
    {
        $result = new NullableQuery($this->getClient([
            'data' => [
                'node' => [
                    '__typename' => 'User',
                    'login' => 'ruudk',
                ],
            ],
        ]))->execute('VXNlcjox');

        self::assertSame('ruudk', $result->node?->login);
    }

    public function testNullableWhenNull(): void
    {
        $result = new NullableQuery($this->getClient([
            'data' => [
                'node' => null,
            ],
        ]))->execute('VXNlcjox');

        self::assertNull($result->node);
    }

    public function testList(): void
    {
        $result = new ListQuery($this->getClient([
            'data' => [
                'nodes' => [
                    [
                        '__typename' => 'Event',
                        'name' => 'Awakenings',
                        'description' => null,
                    ],
                    null,
                ],
                'search' => [
                    [
                        '__typename' => 'User',
                        'login' => 'ruudk',
                    ],
                ],
            ],
        ]))->execute(['RXZlbnQ6MQ==', 'RXZlbnQ6Mg==']);

        self::assertSame('Awakenings', $result->nodes[0]?->name);
        self::assertNull($result->nodes[1]);
        self::assertSame('ruudk', $result->search[0]->userLogin->login);
    }

    public function testListThrowsOnOtherType(): void
    {
        $result = new ListQuery($this->getClient([
            'data' => [
                'nodes' => [],
                'search' => [
                    [
                        '__typename' => 'Event',
                    ],
                ],
            ],
        ]))->execute([]);

        self::assertThrowsNodeNotFound('Field Query.search is Event, expected User', static fn() => count($result->search));
    }

    /**
     * @param callable(): mixed $read
     */
    private static function assertThrowsNodeNotFound(string $expectedMessage, callable $read): void
    {
        try {
            $read();
        } catch (NodeNotFoundException $exception) {
            self::assertSame($expectedMessage, $exception->getMessage());

            return;
        }

        self::fail('Expected a NodeNotFoundException.');
    }
}
