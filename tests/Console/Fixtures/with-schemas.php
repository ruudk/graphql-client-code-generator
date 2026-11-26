<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\TestClient;

$schemas = dirname(__DIR__, 2) . '/Schemas';

// Like SchemasTest, but both schemas read the Checkout directory through the config.
$billing = SchemaConfig::create(
    schema: $schemas . '/Billing.graphql',
    outputDir: $schemas . '/Generated/Billing',
    namespace: 'Ruudk\\GraphQLCodeGenerator\\Schemas\\Generated\\Billing',
    client: TestClient::class,
    name: 'billing',
);

return Config::create(projectDir: dirname(__DIR__, 3))
    ->withInlineProcessingDirectory($schemas . '/Checkout')
    ->withTwigProcessingDirectory($schemas . '/templates')
    ->withSchema($billing->withInlineProcessingDirectory($schemas . '/Billing'))
    ->withSchema(SchemaConfig::create(
        schema: $schemas . '/Shipping.graphql',
        outputDir: $schemas . '/Generated/Shipping',
        namespace: 'Ruudk\\GraphQLCodeGenerator\\Schemas\\Generated\\Shipping',
        client: TestClient::class,
        name: 'shipping',
    ));
