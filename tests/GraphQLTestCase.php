<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use JsonException;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

abstract class GraphQLTestCase extends TestCase
{
    private string $namespace;
    private string $directory;

    #[Override]
    protected function setUp() : void
    {
        parent::setUp();

        $parts = explode('\\', static::class);
        array_pop($parts);
        $this->namespace = implode('\\', $parts);
        $this->directory = __DIR__ . '/' . array_last($parts);
    }

    public function generateExpected() : void
    {
        $this->setUp();
        $this->generate('Expected');
    }

    protected function generate(string $target = 'Actual') : void
    {
        new GraphQLCodeGenerator(
            $this->directory . '/Schema.graphql',
            $this->directory,
            $this->directory . '/' . $target,
            $this->namespace . '\\' . $target,
            TestClient::class,
            false,
            false,
            true,
            true,
            [],
            [],
            [],
            [],
            [],
            [],
            true,
            true,
            false,
            false,
        )->generate();
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
     * @param array<string, mixed> $response
     * @throws JsonException
     */
    protected function getClient(array $response) : TestClient
    {
        $client = new Client();
        $client->addResponse(new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode($response, flags: JSON_THROW_ON_ERROR)));

        return new TestClient($client);
    }

    public function testGenerate() : void
    {
        $this->generate();
        $this->assertActualMatchesExpected();
    }
}
