<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Config\Config;

return Config::create(
    schema: __DIR__ . '/schema.docs.graphql',
    projectDir: __DIR__,
    queriesDir: __DIR__,
    outputDir: __DIR__ . '/Generated',
    namespace: 'Ruudk\GraphQLCodeGenerator\PHPStan\Generated',
    client: 'DoesnotMatter',
);
