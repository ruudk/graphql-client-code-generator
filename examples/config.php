<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;

return Config::create(
    // https://docs.github.com/public/fpt/schema.docs.graphql
    schema: __DIR__ . '/schema.docs.graphql',
    projectDir: __DIR__,
    queriesDir: __DIR__,
    outputDir: __DIR__ . '/Generated',
    namespace: 'Ruudk\GraphQLCodeGenerator\Examples\Generated',
    client: GitHubClient::class,
)
    ->enableDumpDefinition()
    ->enableUseNodeNameForEdgeNodes()
    ->enableUseConnectionNameForConnections()
    ->enableUseEdgeNameForEdges();
