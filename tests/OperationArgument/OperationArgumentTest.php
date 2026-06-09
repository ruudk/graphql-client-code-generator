<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use Override;
use Psr\Http\Message\RequestInterface;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Mutation\CreateThing\CreateThingMutation;
use Symfony\Component\TypeInfo\Type;

final class OperationArgumentTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return Config::create(
            schema: __DIR__ . '/Schema.graphql',
            projectDir: dirname(__DIR__, 2),
            outputDir: __DIR__ . '/Generated',
            namespace: __NAMESPACE__ . '\\Generated',
            client: ActorTestClient::class,
        )
            ->withQueriesDir(__DIR__)
            ->enableDumpOrThrowMethods()
            ->withOperationArgument(
                name: 'actor',
                type: Type::object(Actor::class),
                directive: 'requiresActor',
            );
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testExecuteForwardsActorToClient() : void
    {
        $mock = new Client();
        $mock->addResponse(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode([
            'data' => [
                'createThing' => [
                    'id' => '1',
                    'name' => 'Thing',
                ],
            ],
        ], flags: JSON_THROW_ON_ERROR)));

        $mutation = new CreateThingMutation(new ActorTestClient($mock));
        $data = $mutation->executeOrThrow(new Actor('actor-42'), 'Thing');

        self::assertSame('1', $data->createThing->id);
        self::assertSame('Thing', $data->createThing->name);

        $request = $mock->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('actor-42', $request->getHeaderLine('X-Actor'));
    }
}
