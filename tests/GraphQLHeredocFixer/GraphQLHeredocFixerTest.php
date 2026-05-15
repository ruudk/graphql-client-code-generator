<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQLHeredocFixer;

use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use Ruudk\GraphQLCodeGenerator\PhpCsFixer\GraphQLHeredocFixer;
use SplFileInfo;

final class GraphQLHeredocFixerTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/fixtures';

    public function testFormatsGraphQLNowdoc() : void
    {
        $unformatted = (string) file_get_contents(self::FIXTURES . '/unformatted.php.fixture');
        $expected = (string) file_get_contents(self::FIXTURES . '/formatted.php.fixture');

        self::assertSame($expected, $this->fix($unformatted));
    }

    public function testAlreadyFormattedIsLeftUnchanged() : void
    {
        $formatted = (string) file_get_contents(self::FIXTURES . '/formatted.php.fixture');

        self::assertSame($formatted, $this->fix($formatted));
    }

    public function testInvalidGraphQLIsLeftUntouched() : void
    {
        $invalid = (string) file_get_contents(self::FIXTURES . '/invalid.php.fixture');

        self::assertSame($invalid, $this->fix($invalid));
    }

    public function testNonGraphQLNowdocAndInterpolatedHeredocAreSkipped() : void
    {
        $skipped = (string) file_get_contents(self::FIXTURES . '/skipped.php.fixture');

        self::assertSame($skipped, $this->fix($skipped));
    }

    private function fix(string $code) : string
    {
        $fixer = new GraphQLHeredocFixer();
        $tokens = Tokens::fromCode($code);
        $fixer->fix(new SplFileInfo('Example.php'), $tokens);

        return $tokens->generateCode();
    }
}
