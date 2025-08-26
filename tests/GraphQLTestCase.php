<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use JsonException;
use Override;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Executor\PlanExecutor;
use Symfony\Component\Finder\Finder;

abstract class GraphQLTestCase extends TestCase
{
    private string $namespace;
    private string $directory;
    private Client $client;

    #[Override]
    protected function setUp() : void
    {
        parent::setUp();
        $parts = explode('\\', static::class);
        array_pop($parts);
        $this->namespace = implode('\\', $parts);
        $this->directory = __DIR__ . '/' . $parts[array_key_last($parts)];
        $this->client = new Client();
    }

    public function getConfig() : Config
    {
        return Config::create(
            schema: $this->directory . '/Schema.graphql',
            projectDir: dirname(__DIR__),
            queriesDir: $this->directory,
            outputDir: $this->directory . '/Generated',
            namespace: $this->namespace . '\\Generated',
            client: TestClient::class,
        );
    }

    protected function assertActualMatchesExpected() : void
    {
        $config = $this->getConfig();
        $plan = new Planner($config)->plan();
        $actual = new PlanExecutor($config)->execute($plan);
        // Read expected files from disk
        $expected = [];
        foreach (Finder::create()->files()->in($this->directory . '/Generated') as $file) {
            $expected[$file->getRelativePathname()] = $file->getContents();
        }

        // Sort both arrays by key to ensure consistent comparison
        ksort($actual);
        ksort($expected);
        // Compare - first remove matching files
        foreach ($expected as $path => $content) {
            if (isset($actual[$path]) && $content === $actual[$path]) {
                unset($expected[$path]);
                unset($actual[$path]);
            }
        }

        // Assert remaining are empty
        self::assertSame($expected, $actual);
    }

    /**
     * @param array<string, mixed> $data
     * @throws JsonException
     */
    protected function getClient(array $data, GraphQLRequestMatcher $matcher = new GraphQLRequestMatcher()) : TestClient
    {
        $this->client->on($matcher, new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode($data, flags: JSON_THROW_ON_ERROR)));

        return new TestClient($this->client);
    }
}
