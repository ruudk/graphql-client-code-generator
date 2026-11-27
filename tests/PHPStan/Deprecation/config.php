<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;

$fixtureDir = dirname(__DIR__, 2) . '/Deprecation';

return Config::create(projectDir: $fixtureDir)->withSchema(SchemaConfig::create(
    schema: $fixtureDir . '/Schema.graphql',
    outputDir: $fixtureDir . '/Generated',
    namespace: 'Ruudk\GraphQLCodeGenerator\Deprecation\Generated',
    client: 'DoesnotMatter',
));
