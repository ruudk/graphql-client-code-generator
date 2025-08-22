<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use JsonException;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\TypeInfo\Type;

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

    public function getConfig(string $target = 'Actual') : Config
    {
        return new Config(
            schema: $this->directory . '/Schema.graphql',
            projectDir: dirname(__DIR__),
            queriesDir: $this->directory,
            outputDir: $this->directory . '/' . $target,
            namespace: $this->namespace . '\\' . $target,
            client: TestClient::class,
            dumpMethods: false,
            dumpOrThrows: false,
            dumpDefinition: true,
            useNodeNameForEdgeNodes: true,
            scalars: [
                'IssueId' => [Type::int(), Type::int()],
            ],
            inputObjectTypes: [],
            objectTypes: [],
            enumTypes: [],
            ignoreTypes: [],
            typeInitializers: [],
            useConnectionNameForConnections: true,
            useEdgeNameForEdges: true,
            addNodesOnConnections: true,
            addSymfonyExcludeAttribute: false,
            indexByDirective: true,
            addUnknownCaseToEnums: true,
        );
    }

    protected function assertActualMatchesExpected() : void
    {
        $expected = [];
        foreach (Finder::create()->files()->in($this->directory . '/Expected') as $file) {
            $expected[$file->getRelativePathname()] = str_replace(
                [
                    'namespace ' . $this->namespace . '\\Expected',
                    'use ' . $this->namespace . '\\Expected',
                ],
                [
                    'namespace ' . $this->namespace . '\\Actual',
                    'use ' . $this->namespace . '\\Actual',
                ],
                $file->getContents(),
            );
        }

        $actual = [];
        foreach (Finder::create()->files()->in($this->directory . '/Actual') as $file) {
            $actual[$file->getRelativePathname()] = $file->getContents();
        }

        foreach ($expected as $path => $contents) {
            if (isset($actual[$path]) && $contents === $actual[$path]) {
                unset($expected[$path]);
                unset($actual[$path]);
            }
        }

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
