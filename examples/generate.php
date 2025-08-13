<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;

require_once __DIR__ . '/../vendor/autoload.php';

new CodeGenerator(
    // https://docs.github.com/public/fpt/schema.docs.graphql
    __DIR__ . '/schema.docs.graphql',
    __DIR__,
    __DIR__ . '/Generated',
    'Ruudk\GraphQLCodeGenerator\Examples\Generated',
    GitHubClient::class,
    false,
    false,
    true,
)->generate();
