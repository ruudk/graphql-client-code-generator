<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\TestClient;

return Config::create(projectDir: '/app')
    ->withInlineProcessingDirectory('/app/src')
    ->withSchema(SchemaConfig::create(
        schema: 'type Query { viewer: String }',
        outputDir: '/app/src/Generated/Billing',
        namespace: 'App\\Generated\\Billing',
        client: TestClient::class,
    ));
