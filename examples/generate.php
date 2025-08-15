<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;
use Ruudk\GraphQLCodeGenerator\GraphQLCodeGenerator;

require_once __DIR__ . '/../vendor/autoload.php';

new GraphQLCodeGenerator(
    // https://docs.github.com/public/fpt/schema.docs.graphql
    __DIR__ . '/schema.docs.graphql',
    __DIR__,
    __DIR__ . '/Generated',
    'Ruudk\GraphQLCodeGenerator\Examples\Generated',
    GitHubClient::class,
    false,
    false,
    true,
    true,
    [],
    [],
    [],
    [],
    [],
    [],
    true,
    true,
    false,
    false,
)->generate();
