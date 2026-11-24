<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\TestClient;

$tests = dirname(__DIR__, 2);

return [
    Config::create(
        schema: $tests . '/Scope/Schema.graphql',
        projectDir: dirname($tests),
        outputDir: $tests . '/Scope/Generated',
        namespace: 'Ruudk\\GraphQLCodeGenerator\\Scope\\Generated',
        client: TestClient::class,
    )
        ->withInlineProcessingDirectory($tests . '/Scope')
        ->withScope('billing'),
    Config::create(
        schema: $tests . '/ScopeShipping/Schema.graphql',
        projectDir: dirname($tests),
        outputDir: $tests . '/ScopeShipping/Generated',
        namespace: 'Ruudk\\GraphQLCodeGenerator\\ScopeShipping\\Generated',
        client: TestClient::class,
    )
        ->withInlineProcessingDirectory($tests . '/Scope')
        ->withScope('shipping'),
];
