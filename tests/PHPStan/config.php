<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;

return Config::create(projectDir: __DIR__)->withSchema(SchemaConfig::create(
    schema: __DIR__ . '/schema.docs.graphql',
    outputDir: __DIR__ . '/Generated',
    namespace: 'Ruudk\GraphQLCodeGenerator\PHPStan\Generated',
    client: 'DoesnotMatter',
)->withQueriesDir(__DIR__));
