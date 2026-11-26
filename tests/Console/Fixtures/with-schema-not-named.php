<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\TestClient;

$schemas = dirname(__DIR__, 2) . '/Schemas';

// Both schemas read the Billing directory, whose InvoiceController does not name its schema.
$billing = SchemaConfig::create(
    schema: $schemas . '/Billing.graphql',
    outputDir: $schemas . '/Generated/Billing',
    namespace: 'Ruudk\\GraphQLCodeGenerator\\Schemas\\Generated\\Billing',
    client: TestClient::class,
    name: 'billing',
);

return Config::create(projectDir: dirname(__DIR__, 3))
    ->withInlineProcessingDirectory($schemas . '/Checkout', $schemas . '/Billing')
    ->withSchema($billing)
    ->withSchema(SchemaConfig::create(
        schema: $schemas . '/Shipping.graphql',
        outputDir: $schemas . '/Generated/Shipping',
        namespace: 'Ruudk\\GraphQLCodeGenerator\\Schemas\\Generated\\Shipping',
        client: TestClient::class,
        name: 'shipping',
    ));
