<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Query\Test\TestQuery;

final class HooksWithListReturnTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withHook(FindUsersByIdsHook::class);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $findUsersByIds = new FindUsersByIdsHook([
            'user-123' => new User('user-123', 'Alice'),
            'user-456' => new User('user-456', 'Bob'),
        ]);

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'viewer' => [
                        'login' => 'ruudk',
                        'projects' => [
                            [
                                'name' => 'GraphQL Code Generator',
                                'contributorIds' => ['user-123', 'user-456'],
                            ],
                            [
                                'name' => 'Some Other Project',
                                'contributorIds' => ['user-123'],
                            ],
                        ],
                    ],
                ],
            ]),
            [
                'findUsersByIds' => $findUsersByIds,
            ],
        )->execute();

        self::assertSame('ruudk', $result->viewer->login);
        self::assertCount(2, $result->viewer->projects);

        [$first, $second] = $result->viewer->projects;

        self::assertCount(2, $first->contributors);
        self::assertSame('Alice', $first->contributors[0]->name);
        self::assertSame('Bob', $first->contributors[1]->name);

        self::assertCount(1, $second->contributors);
        self::assertSame('Alice', $second->contributors[0]->name);
    }
}
