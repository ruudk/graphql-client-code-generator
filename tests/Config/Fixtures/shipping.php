<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\TestClient;

return Config::create(projectDir: '/app')
    ->withInlineProcessingDirectory('/app/src/Checkout')
    ->withSchema(SchemaConfig::create(
        schema: 'type Query { viewer: String }',
        outputDir: '/app/src/Generated/Shipping',
        namespace: 'App\\Generated\\Shipping',
        client: TestClient::class,
    ));
