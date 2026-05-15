<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use JsonException;
use Override;
use TwigCsFixer\Rules\AbstractFixableRule;
use TwigCsFixer\Token\Token;
use TwigCsFixer\Token\Tokens;

/**
 * Formats the GraphQL operations inside `{% graphql %}...{% endgraphql %}`
 * blocks using webonyx/graphql-php so they follow a single canonical style.
 */
final class GraphQLFormatterRule extends AbstractFixableRule
{
    #[Override]
    protected function process(int $tokenIndex, Tokens $tokens) : void
    {
        $token = $tokens->get($tokenIndex);

        if ( ! $token->isMatching(Token::BLOCK_NAME_TYPE, 'graphql')) {
            return;
        }

        $blockEndIndex = $tokens->findNext(Token::BLOCK_END_TYPE, $tokenIndex);

        if ($blockEndIndex === false) {
            return;
        }

        $endNameIndex = false;

        for ($i = $blockEndIndex + 1; $tokens->has($i); ++$i) {
            if ($tokens->get($i)->isMatching(Token::BLOCK_NAME_TYPE, 'endgraphql')) {
                $endNameIndex = $i;

                break;
            }
        }

        if ($endNameIndex === false) {
            return;
        }

        $endBlockStartIndex = $tokens->findPrevious(Token::BLOCK_START_TYPE, $endNameIndex, $blockEndIndex);

        if ($endBlockStartIndex === false) {
            return;
        }

        $contentStart = $blockEndIndex + 1;
        $contentEnd = $endBlockStartIndex - 1;

        if ($contentStart > $contentEnd) {
            return;
        }

        $raw = '';

        for ($i = $contentStart; $i <= $contentEnd; ++$i) {
            $contentToken = $tokens->get($i);

            // Bail out when the block embeds Twig (variables, tags, comments);
            // reconstructing the original source from those tokens is not safe.
            if ($contentToken->isMatching([
                Token::BLOCK_START_TYPE,
                Token::VAR_START_TYPE,
                Token::COMMENT_START_TYPE,
                Token::INLINE_COMMENT_START_TYPE,
            ])) {
                return;
            }

            $raw .= $contentToken->getValue();
        }

        if (trim($raw) === '') {
            return;
        }

        try {
            $formatted = rtrim(Printer::doPrint(Parser::parse($raw)), "\r\n");
        } catch (JsonException | SyntaxError $error) {
            $this->addError(
                sprintf('Unable to parse GraphQL: %s', $error->getMessage()),
                $token,
            );

            return;
        }

        $indent = $this->resolveIndent($tokens, $tokenIndex);

        $expected = "\n";

        foreach (explode("\n", $formatted) as $line) {
            $expected .= ($line === '' ? '' : $indent . $line) . "\n";
        }

        $expected .= $indent;

        if ($raw === $expected) {
            return;
        }

        $fixer = $this->addFixableError(
            'The GraphQL operation is not correctly formatted.',
            $token,
        );

        if ($fixer === null) {
            return;
        }

        $fixer->beginChangeSet();
        $fixer->replaceToken($contentStart, $expected);

        for ($i = $contentStart + 1; $i <= $contentEnd; ++$i) {
            $fixer->replaceToken($i, '');
        }

        $fixer->endChangeSet();
    }

    private function resolveIndent(Tokens $tokens, int $blockNameIndex) : string
    {
        $blockStartIndex = $tokens->findPrevious(Token::BLOCK_START_TYPE, $blockNameIndex);

        if ($blockStartIndex === false || ! $tokens->has($blockStartIndex - 1)) {
            return '';
        }

        $previous = $tokens->get($blockStartIndex - 1);

        if ($previous->isMatching(Token::INDENT_TOKENS)) {
            return $previous->getValue();
        }

        return '';
    }
}
