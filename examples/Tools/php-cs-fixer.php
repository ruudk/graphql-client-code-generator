<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use Ruudk\GraphQLCodeGenerator\PhpCsFixer\GraphQLHeredocFixer;

return new Config()
    ->setFinder(Finder::create()->in(__DIR__ . '/src'))
    ->registerCustomFixers([new GraphQLHeredocFixer()])
    ->setRules([
        '@PER-CS' => true,
        'Ruudk/graphql_heredoc' => true,
    ]);
