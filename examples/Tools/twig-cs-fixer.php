<?php

declare(strict_types=1);

use Ruudk\GraphQLCodeGenerator\Twig\GraphQLFormatterRule;
use Ruudk\GraphQLCodeGenerator\Twig\GraphQLTokenParser;
use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());
$ruleset->addRule(new GraphQLFormatterRule());

$config = new Config();
$config->allowNonFixableRules();
$config->setRuleset($ruleset);
$config->setFinder(Finder::create()->in(__DIR__ . '/templates'));
$config->addTokenParser(new GraphQLTokenParser());

return $config;
