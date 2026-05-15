<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PhpCsFixer;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use JsonException;
use Override;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Formats the GraphQL operation inside `<<<'GRAPHQL' ... GRAPHQL` nowdoc
 * strings using webonyx/graphql-php so it follows a single canonical style.
 */
final class GraphQLHeredocFixer implements FixerInterface
{
    private const string INDENT = '    ';

    #[Override]
    public function getName() : string
    {
        return 'Ruudk/graphql_heredoc';
    }

    #[Override]
    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition(
            "GraphQL inside <<<'GRAPHQL' nowdoc strings must be formatted with the webonyx/graphql-php printer.",
            [
                new CodeSample(
                    <<<'PHP'
                        <?php
                        $query = <<<'GRAPHQL'
                            query  Foo{ id }
                            GRAPHQL;

                        PHP,
                ),
            ],
        );
    }

    #[Override]
    public function isCandidate(Tokens $tokens) : bool
    {
        return $tokens->isTokenKindFound(T_START_HEREDOC);
    }

    #[Override]
    public function isRisky() : bool
    {
        return false;
    }

    #[Override]
    public function getPriority() : int
    {
        // Run before the built-in heredoc_indentation fixer (priority -26)
        // so the re-indentation it applies afterwards stays a no-op.
        return 1;
    }

    #[Override]
    public function supports(SplFileInfo $file) : bool
    {
        return true;
    }

    #[Override]
    public function fix(SplFileInfo $file, Tokens $tokens) : void
    {
        if ($tokens->count() === 0 || ! $this->isCandidate($tokens)) {
            return;
        }

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if ( ! $token->isGivenKind(T_START_HEREDOC)) {
                continue;
            }

            // Only single-quoted nowdoc with the GRAPHQL label. A heredoc
            // would interpolate GraphQL `$variables` as PHP variables.
            if (preg_match('/^<<<[ \t]*\'GRAPHQL\'/', $token->getContent()) !== 1) {
                continue;
            }

            $endIndex = $tokens->getNextTokenOfKind($index, [[T_END_HEREDOC]]);

            if ($endIndex === null || $endIndex === $index + 1) {
                continue;
            }

            $bodyIndex = $index + 1;
            $bodyToken = $tokens[$bodyIndex];

            if ( ! $bodyToken->isGivenKind(T_ENCAPSED_AND_WHITESPACE) || $bodyIndex + 1 !== $endIndex) {
                continue;
            }

            try {
                $formatted = rtrim(Printer::doPrint(Parser::parse($bodyToken->getContent())), "\r\n");
            } catch (JsonException | SyntaxError) {
                continue;
            }

            $indent = $this->detectIndent($tokens, $index) . self::INDENT;

            $body = '';

            foreach (explode("\n", $formatted) as $line) {
                $body .= ($line === '' ? '' : $indent . $line) . "\n";
            }

            $endContent = $indent . 'GRAPHQL';

            if ($body === $bodyToken->getContent() && $tokens[$endIndex]->getContent() === $endContent) {
                continue;
            }

            $tokens[$bodyIndex] = new Token([T_ENCAPSED_AND_WHITESPACE, $body]);
            $tokens[$endIndex] = new Token([T_END_HEREDOC, $endContent]);
        }
    }

    private function detectIndent(Tokens $tokens, int $index) : string
    {
        while (true) {
            $whitespaceIndex = $tokens->getPrevTokenOfKind($index, [[T_WHITESPACE]]);

            if ($whitespaceIndex === null) {
                return '';
            }

            $whitespace = $tokens[$whitespaceIndex]->getContent();

            $newlinePosition = strrpos($whitespace, "\n");

            if ($newlinePosition === false) {
                $index = $whitespaceIndex;

                continue;
            }

            return substr($whitespace, $newlinePosition + 1);
        }
    }
}
