<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\Console\UnusedHookForTesting;
use Ruudk\GraphQLCodeGenerator\Hooks\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\TestClient;

$hooksDir = dirname(__DIR__, 2) . '/Hooks';

return Config::create(projectDir: dirname(__DIR__, 3))->withSchema(
    SchemaConfig::create(
        schema: $hooksDir . '/Schema.graphql',
        outputDir: $hooksDir . '/Generated',
        namespace: 'Ruudk\\GraphQLCodeGenerator\\Hooks\\Generated',
        client: TestClient::class,
    )
        ->withQueriesDir($hooksDir)
        ->withHook(FindUserByIdHook::class)
        ->withHook(UnusedHookForTesting::class),
);
