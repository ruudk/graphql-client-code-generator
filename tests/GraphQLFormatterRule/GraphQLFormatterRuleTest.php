<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQLFormatterRule;

use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Twig\GraphQLFormatterRule;
use SplFileInfo;
use TwigCsFixer\Environment\StubbedEnvironment;
use TwigCsFixer\Report\Report;
use TwigCsFixer\Report\Violation;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Runner\Fixer;
use TwigCsFixer\Runner\Linter;
use TwigCsFixer\Token\Tokenizer;

final class GraphQLFormatterRuleTest extends TestCase
{
    private const string TEMPLATES = __DIR__ . '/templates';

    public function testFixesUnformattedGraphQLBlocks() : void
    {
        $unformatted = (string) file_get_contents(self::TEMPLATES . '/unformatted.html.twig.fixture');
        $expected = (string) file_get_contents(self::TEMPLATES . '/formatted.html.twig.fixture');

        self::assertSame($expected, $this->fix($unformatted));
    }

    public function testAlreadyFormattedBlocksAreLeftUnchanged() : void
    {
        $formatted = (string) file_get_contents(self::TEMPLATES . '/formatted.html.twig.fixture');

        self::assertSame($formatted, $this->fix($formatted));
        self::assertSame([], $this->lint(self::TEMPLATES . '/formatted.html.twig.fixture')->getViolations());
    }

    public function testReportsViolationForUnformattedBlock() : void
    {
        $violations = $this->lint(self::TEMPLATES . '/unformatted.html.twig.fixture')->getViolations();

        self::assertCount(1, $violations);
        self::assertSame(Violation::LEVEL_ERROR, $violations[0]->getLevel());
        self::assertSame('The GraphQL operation is not correctly formatted.', $violations[0]->getMessage());
    }

    public function testReportsErrorForInvalidGraphQL() : void
    {
        $invalid = (string) file_get_contents(self::TEMPLATES . '/invalid.html.twig.fixture');

        self::assertSame($invalid, $this->fix($invalid), 'Invalid GraphQL must be left untouched.');

        $violations = $this->lint(self::TEMPLATES . '/invalid.html.twig.fixture')->getViolations();

        self::assertCount(1, $violations);
        self::assertSame(Violation::LEVEL_ERROR, $violations[0]->getLevel());
        self::assertStringContainsString('Unable to parse GraphQL', $violations[0]->getMessage());
    }

    private function fix(string $content) : string
    {
        $fixer = new Fixer(new Tokenizer(new StubbedEnvironment()));

        return $fixer->fixFile($content, $this->ruleset());
    }

    private function lint(string $filePath) : Report
    {
        $env = new StubbedEnvironment();
        $linter = new Linter($env, new Tokenizer($env));

        return $linter->run([new SplFileInfo($filePath)], $this->ruleset());
    }

    private function ruleset() : Ruleset
    {
        $ruleset = new Ruleset();
        $ruleset->addRule(new GraphQLFormatterRule());

        return $ruleset;
    }
}
