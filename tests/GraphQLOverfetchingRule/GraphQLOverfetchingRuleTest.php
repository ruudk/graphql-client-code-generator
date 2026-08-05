<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQLOverfetchingRule;

use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\Twig\GraphQLOverfetchingRule;
use SplFileInfo;
use TwigCsFixer\Environment\StubbedEnvironment;
use TwigCsFixer\Report\Report;
use TwigCsFixer\Report\Violation;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Runner\Linter;
use TwigCsFixer\Token\Tokenizer;

final class GraphQLOverfetchingRuleTest extends TestCase
{
    private const string TEMPLATES = __DIR__ . '/templates';

    public function testNoViolationWhenEveryFetchedFieldIsUsed() : void
    {
        $violations = $this->lint(self::TEMPLATES . '/no_overfetching.html.twig.fixture')->getViolations();

        self::assertSame([], $violations);
    }

    public function testReportsUnusedTopLevelField() : void
    {
        $violations = $this->lint(self::TEMPLATES . '/overfetching.html.twig.fixture')->getViolations();

        self::assertCount(1, $violations);
        self::assertSame(Violation::LEVEL_ERROR, $violations[0]->getLevel());
        self::assertSame(
            '`data.viewer` is fetched in the GraphQL operation but never used in the template (overfetching).',
            $violations[0]->getMessage(),
        );
    }

    public function testReportsUnusedNestedField() : void
    {
        $violations = $this->lint(self::TEMPLATES . '/overfetching_nested.html.twig.fixture')->getViolations();

        self::assertCount(1, $violations);
        self::assertSame(Violation::LEVEL_ERROR, $violations[0]->getLevel());
        self::assertSame(
            '`data.viewer.name` is fetched in the GraphQL operation but never used in the template (overfetching).',
            $violations[0]->getMessage(),
        );
    }

    private function lint(string $filePath) : Report
    {
        $env = new StubbedEnvironment();
        $linter = new Linter($env, new Tokenizer($env));

        $ruleset = new Ruleset();
        $ruleset->addRule(new GraphQLOverfetchingRule());

        return $linter->run([new SplFileInfo($filePath)], $ruleset);
    }
}
