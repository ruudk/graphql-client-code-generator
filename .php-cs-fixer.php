<?php

declare(strict_types=1);

use PhpCsFixer\Finder;
use Ruudk\GraphQLCodeGenerator\PhpCsFixer\GraphQLHeredocFixer;
use Ticketswap\PhpCsFixerConfig\Fixers;
use Ticketswap\PhpCsFixerConfig\NameWrapper;
use Ticketswap\PhpCsFixerConfig\PhpCsFixerConfigFactory;
use Ticketswap\PhpCsFixerConfig\RuleSet\TicketSwapRuleSet;

$finder = Finder::create()
    ->in(__DIR__ . '/examples')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->notPath(['Generated'])
    ->append([
        __DIR__ . '/.php-cs-fixer.php',
        __DIR__ . '/bin/graphql-client-code-generator',
        __DIR__ . '/composer-dependency-analyser.php',
        __DIR__ . '/phpstan.php',
    ]);

return PhpCsFixerConfigFactory::create(
    TicketSwapRuleSet::create()->withCustomFixers(
        new Fixers(
            new NameWrapper(new GraphQLHeredocFixer()),
        ),
    ),
)->setFinder($finder);
