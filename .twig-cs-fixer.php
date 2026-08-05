<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Twig\GraphQLFormatterRule;
use Ruudk\GraphQLCodeGenerator\Twig\GraphQLOverfetchingRule;
use Ruudk\GraphQLCodeGenerator\Twig\GraphQLTokenParser;
use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();

$ruleset->addStandard(new TwigCsFixer());
$ruleset->addRule(new GraphQLFormatterRule());
$ruleset->addRule(new GraphQLOverfetchingRule());

$finder = Finder::create()
    ->in('tests');

$config = new Config();
$config->allowNonFixableRules();
$config->setCacheFile(__DIR__ . '/.twig-cs-fixer.cache');
$config->setRuleset($ruleset);
$config->setFinder($finder);
$config->addTokenParser(new GraphQLTokenParser());

return $config;
