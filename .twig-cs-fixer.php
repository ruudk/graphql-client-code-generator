<?php

use Ruudk\GraphQLCodeGenerator\Twig\GraphQLTokenParser;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Config\Config;

$ruleset = new Ruleset();

$ruleset->addStandard(new TwigCsFixer());

$finder = Finder::create()
    ->in('tests');

$config = new Config();
$config->allowNonFixableRules();
$config->setCacheFile(__DIR__ . '/.twig-cs-fixer.cache');
$config->setRuleset($ruleset);
$config->setFinder($finder);
$config->addTokenParser(new GraphQLTokenParser());

return $config;

