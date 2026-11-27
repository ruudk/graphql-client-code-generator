<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarInputType;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture\Handle;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Input\UserFilter;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Query\Test\TestQuery;
use Symfony\Component\TypeInfo\Type;

final class ScalarInputTypeTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()
            ->withScalar('ID', Type::string(), inputType: Type::object(UserId::class))
            ->withScalar('Handle', Type::string(), inputType: Type::union(Type::object(Handle::class), Type::string()));
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery(): void
    {
        $result = new TestQuery($this->getClient(
            [
                'data' => [
                    'user' => [
                        'id' => 'VXNlcjox',
                        'name' => 'Ruud',
                    ],
                    'userByHandle' => [
                        'id' => 'VXNlcjox',
                    ],
                    'users' => [
                        [
                            'id' => 'VXNlcjoy',
                        ],
                    ],
                    'search' => [],
                ],
            ],
            new GraphQLRequestMatcher([
                'id' => 'VXNlcjox',
                'handle' => 'ruudk',
                'ids' => ['VXNlcjoy'],
                'filter' => [
                    'ownerId' => 'VXNlcjo0',
                    'managerId' => null,
                    'memberIds' => ['VXNlcjo1'],
                    'name' => 'Ruud',
                    'handle' => 'kamphuis',
                ],
                'after' => 'VXNlcjoz',
                'name' => 'Kamphuis',
            ]),
        ))->execute(
            new UserId(1),
            new Handle('ruudk'),
            [new UserId(2)],
            new UserFilter(new UserId(4), memberIds: [new UserId(5)], name: 'Ruud', handle: 'kamphuis'),
            new UserId(3),
            'Kamphuis',
        );

        self::assertSame('VXNlcjox', $result->user?->id);
        self::assertSame('VXNlcjoy', $result->users[0]->id);
    }
}
