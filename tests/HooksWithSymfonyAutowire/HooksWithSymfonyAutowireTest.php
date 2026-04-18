<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithSymfonyAutowire;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksWithSymfonyAutowire\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class HooksWithSymfonyAutowireTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withHook(FindUserByIdHook::class)
            ->enableSymfonyAutowireHooks();
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
            $this->getClient($this->getResponseData()),
            [
                'findUserById' => $findUserById,
            ],
        )->execute();

        $this->assertResult($result);
    }

    /**
     * Exercises the generated `#[Autowire]` attribute by letting a real Symfony
     * container build the TestQuery. If the attribute shape is wrong, compilation
     * or service resolution fails here.
     */
    public function testQueryWithContainer() : void
    {
        $client = $this->getClient($this->getResponseData());

        $container = new ContainerBuilder();

        $container->register(TestClient::class)
            ->setSynthetic(true)
            ->setPublic(true);

        $container->register(FindUserByIdHook::class)
            ->setArgument('$users', [
                'user-123' => new User('user-123', 'https://example.com/avatars/123.png'),
                'user-456' => new User('user-456', 'https://example.com/avatars/456.png'),
            ]);

        $container->register(TestQuery::class)
            ->setArgument('$client', new Reference(TestClient::class))
            ->setAutowired(true)
            ->setPublic(true);

        $container->compile();
        $container->set(TestClient::class, $client);

        $query = $container->get(TestQuery::class);
        self::assertInstanceOf(TestQuery::class, $query);

        $this->assertResult($query->execute());
    }

    /**
     * @return array<string, mixed>
     */
    private function getResponseData() : array
    {
        return [
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
        ];
    }

    private function assertResult(Generated\Query\Test\Data $result) : void
    {
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
