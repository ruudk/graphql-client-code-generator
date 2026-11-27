<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder;

use Exception;
use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\Executor\PlanExecutor;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Planner;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\ProjectId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\TypeInfo\Type;

final class ScalarDecoderTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return $this->getConfigWithoutAutowiredDecoders()->enableSymfonyAutowireDecoders();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    /**
     * Autowiring the hooks leaves the decoders to whoever builds the query.
     *
     * @throws Exception
     */
    public function testAutowiresTheDecodersApartFromTheHooks(): void
    {
        $config = $this->getConfigWithoutAutowiredDecoders();
        $files = new PlanExecutor($config)->execute(
            new Planner(Config::create(dirname(__DIR__, 2))->withSchema($config), $config)->plan(),
        );
        $query = $files[$config->outputDir . '/Query/Test/TestQuery.php'] ?? '';

        self::assertStringContainsString(<<<'PHP'
                    #[Autowire([
                        'findOwner' => new Autowire(service: FindOwnerHook::class)
                    ])]
                    private array $hooks,
                    private array $decoders,
            PHP, $query);
    }

    private function getConfigWithoutAutowiredDecoders(): SchemaConfig
    {
        return parent::getConfig()
            ->enableIndexByDirective()
            ->enableNarrowDirective()
            ->withHook(FindOwnerHook::class)
            ->withScalar('ID', Type::string(), Type::object(NodeId::class), inputType: Type::object(NodeId::class))
            ->enableSymfonyAutowireHooks()
            ->withScalarDecoder('ID', NodeIdDecoder::class, 'decode', service: 'app.node_id_decoder')
            ->withScalarFieldType('ID', static fn(string $type, string $field): ?Type => match ([$type, $field]) {
                ['User', 'id'], ['Project', 'ownerId'] => Type::object(UserId::class),
                ['Project', 'id'] => Type::object(ProjectId::class),
                default => null,
            });
    }

    public function testQuery(): void
    {
        $result = new TestQuery(
            $this->getClient($this->getResponseData()),
            [
                'findOwner' => new FindOwnerHook([1 => 'Ruud']),
            ],
            [
                'ID' => new NodeIdDecoder(),
            ],
        )->execute(new UserId(1));

        $this->assertResult($result);
    }

    /**
     * Lets a Symfony container build the query: the decoder is wired by the service id it was given.
     */
    public function testQueryWithContainer(): void
    {
        $client = $this->getClient($this->getResponseData());

        $container = new ContainerBuilder();
        $container->register(TestClient::class)->setSynthetic(true)->setPublic(true);
        $container->register('app.node_id_decoder', NodeIdDecoder::class);
        $container->register(FindOwnerHook::class)->setArgument('$names', [1 => 'Ruud']);
        $container
            ->register(TestQuery::class)
            ->setArgument('$client', new Reference(TestClient::class))
            ->setAutowired(true)
            ->setPublic(true);
        $container->compile();
        $container->set(TestClient::class, $client);

        $query = $container->get(TestQuery::class);
        self::assertInstanceOf(TestQuery::class, $query);

        $this->assertResult($query->execute(new UserId(1)));
    }

    /**
     * @return array<string, mixed>
     */
    private function getResponseData(): array
    {
        return [
            'data' => [
                'node' => [
                    '__typename' => 'User',
                    'id' => base64_encode('User:1'),
                    'name' => 'Ruud',
                ],
                'narrowed' => [
                    '__typename' => 'Project',
                    'id' => base64_encode('Project:3'),
                    'name' => 'GraphQL Client Code Generator',
                ],
                'viewer' => [
                    'id' => base64_encode('User:1'),
                    'name' => 'Ruud',
                    'friendIds' => [base64_encode('User:2'), base64_encode('Project:3')],
                    'bestFriendId' => null,
                    'manager' => [
                        'id' => base64_encode('User:4'),
                    ],
                ],
                'projects' => [
                    [
                        'id' => base64_encode('Project:3'),
                        'name' => 'GraphQL Client Code Generator',
                        'ownerId' => base64_encode('User:1'),
                    ],
                ],
            ],
        ];
    }

    private function assertResult(Data $result): void
    {
        // Node.id has no narrower type: the decoder picks the class from the id
        self::assertEquals(new UserId(1), $result->node?->id);
        // User.id has one, so the decoder is asked for a UserId
        self::assertEquals(new UserId(1), $result->node?->asUser?->id);

        // @narrow plans the fields on the object type, so Node.id becomes Project.id
        self::assertEquals(new ProjectId(3), $result->narrowed?->id);

        self::assertEquals(new UserId(1), $result->viewer->id);
        self::assertEquals([new UserId(2), new ProjectId(3)], $result->viewer->friendIds);
        self::assertNull($result->viewer->bestFriendId);
        self::assertEquals(new UserId(4), $result->viewer->manager?->id);

        // The keys of @indexBy stay the JSON value
        $project = $result->projects[base64_encode('Project:3')];
        self::assertEquals(new ProjectId(3), $project->projectFields->id);
        // The hook reads ownerId as a UserId too
        self::assertSame('Ruud', $project->owner);
    }
}
