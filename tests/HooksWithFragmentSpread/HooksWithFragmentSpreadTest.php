<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class HooksWithFragmentSpreadTest extends GraphQLTestCase
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
     * Exercises the `#[Autowire]` attribute by letting a real Symfony container
     * build the TestQuery. If the attribute shape is wrong or the hook is not
     * forwarded through the fragment-spread chain, compilation or service
     * resolution fails here.
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

        self::assertSame('GraphQL Code Generator', $first->projectListing->projectSummary->name);
        self::assertNotNull($first->projectListing->projectSummary->user);
        self::assertSame('user-123', $first->projectListing->projectSummary->user->id);
        self::assertSame('https://example.com/avatars/123.png', $first->projectListing->projectSummary->user->avatar);

        self::assertSame('A project', $second->projectListing->description);
        self::assertNotNull($second->projectListing->projectSummary->user);
        self::assertSame('user-456', $second->projectListing->projectSummary->user->id);
        self::assertSame('https://example.com/avatars/456.png', $second->projectListing->projectSummary->user->avatar);
    }
}
