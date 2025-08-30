<?php

declare(strict_types=1);

use Http\Discovery\Psr18ClientDiscovery;
use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;
use Symfony\Component\Dotenv\Dotenv;
use Webmozart\Assert\Assert;

return Config::create(
    // https://docs.github.com/public/fpt/schema.docs.graphql
    schema: __DIR__ . '/schema.docs.graphql',
    projectDir: __DIR__,
    queriesDir: __DIR__,
    outputDir: __DIR__ . '/Generated',
    namespace: 'Ruudk\GraphQLCodeGenerator\Examples\Generated',
    client: GitHubClient::class,
)
    ->withIntrospectionClient(function () {
        $dotenv = new Dotenv();
        $dotenv->bootEnv(__DIR__ . '/.env.local');

        Assert::keyExists($_ENV, 'GITHUB_TOKEN');
        $token = $_ENV['GITHUB_TOKEN'];
        Assert::stringNotEmpty($token);

        return new GitHubClient(Psr18ClientDiscovery::find(), $token);
    })
    ->enableDumpDefinition()
    ->enableUseNodeNameForEdgeNodes()
    ->enableUseConnectionNameForConnections()
    ->enableUseEdgeNameForEdges();
