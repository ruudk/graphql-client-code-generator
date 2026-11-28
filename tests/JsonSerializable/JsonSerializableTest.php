<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Narrow\NarrowQuery;
use Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Test\TestQuery;

final class JsonSerializableTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()
            ->enableJsonSerializable()
            ->enableIndexByDirective()
            ->enableFlattenDirective()
            ->enableThrowWhenNullDirective()
            ->enableNarrowDirective();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testSerializesTheSelection(): void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'viewer' => [
                    '__typename' => 'User',
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                    'role' => 'ADMIN',
                ],
                'events' => [
                    [
                        '__typename' => 'Event',
                        'id' => 'event-1',
                        'name' => 'Awakenings',
                        'description' => null,
                        'location' => [
                            'locationName' => 'Spaarnwoude',
                            'city' => [
                                'cityName' => 'Haarlem',
                            ],
                        ],
                        'tags' => ['techno'],
                        'slug' => 'awakenings',
                    ],
                ],
                'search' => [
                    [
                        '__typename' => 'Event',
                        'id' => 'event-1',
                        'name' => 'Awakenings',
                    ],
                    [
                        '__typename' => 'Venue',
                        'id' => 'venue-1',
                        'title' => 'Gashouder',
                    ],
                    [
                        '__typename' => 'User',
                        'id' => 'user-1',
                    ],
                ],
            ],
        ]))->execute();

        self::assertSame(
            [
                'events' => [
                    'event-1' => [
                        '__typename' => 'Event',
                        'cityName' => 'Haarlem',
                        'description' => null,
                        'id' => 'event-1',
                        'locationName' => 'Spaarnwoude',
                        'name' => 'Awakenings',
                        'slug' => 'awakenings',
                        'tags' => ['techno'],
                    ],
                ],
                'search' => [
                    [
                        'id' => 'event-1',
                        'name' => 'Awakenings',
                    ],
                    [
                        'id' => 'venue-1',
                        'title' => 'Gashouder',
                    ],
                    [],
                ],
                'viewer' => [
                    'name' => 'Ruud Kamphuis',
                    'login' => 'ruudk',
                    'role' => 'ADMIN',
                ],
            ],
            json_decode(json_encode($result, flags: JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public function testSerializesANarrowedType(): void
    {
        $result = new NarrowQuery($this->getClient([
            'data' => [
                'event' => [
                    '__typename' => 'Event',
                    'id' => 'event-1',
                    'name' => 'Awakenings',
                ],
            ],
        ]))->execute('event-1');

        self::assertSame('{"event":{"id":"event-1","name":"Awakenings"}}', json_encode(
            $result,
            flags: JSON_THROW_ON_ERROR,
        ));
    }
}
