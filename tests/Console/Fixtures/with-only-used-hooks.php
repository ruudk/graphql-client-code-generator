<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Hooks\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\TestClient;

$hooksDir = __DIR__ . '/../../Hooks';

return Config::create(
    schema: $hooksDir . '/Schema.graphql',
    projectDir: dirname(__DIR__, 3),
    outputDir: $hooksDir . '/Generated',
    namespace: 'Ruudk\\GraphQLCodeGenerator\\Hooks\\Generated',
    client: TestClient::class,
)
    ->withQueriesDir($hooksDir)
    ->withHook(FindUserByIdHook::class);
