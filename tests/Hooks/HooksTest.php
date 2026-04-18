<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Hooks;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Hooks\Generated\Query\Test\TestQuery;

final class HooksTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withHook(FindUserByIdHook::class);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $findUserById = new FindUserByIdHook([
            'user-123' => new User('user-123', 'https://example.com/avatars/123.png'),
            'user-456' => new User('user-456', 'https://example.com/avatars/456.png'),
        ]);

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'viewer' => [
                        'login' => 'ruudk',
                        'projects' => [
                            [
                                'name' => 'GraphQL Code Generator',
                                'description' => null,
                                'creator' => [
                                    'id' => 'user-123',
                                ],
                            ],
                            [
                                'name' => 'Some Other Project',
                                'description' => 'A project',
                                'creator' => [
                                    'id' => 'user-456',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            [
                'findUserById' => $findUserById,
            ],
        )->execute();

        self::assertSame('ruudk', $result->viewer->login);
        self::assertCount(2, $result->viewer->projects);

        [$first, $second] = $result->viewer->projects;

        self::assertSame('GraphQL Code Generator', $first->name);
        self::assertNotNull($first->user);
        self::assertSame('user-123', $first->user->id);
        self::assertSame('https://example.com/avatars/123.png', $first->user->avatar);

        self::assertSame('Some Other Project', $second->name);
        self::assertNotNull($second->user);
        self::assertSame('user-456', $second->user->id);
        self::assertSame('https://example.com/avatars/456.png', $second->user->avatar);
    }
}
