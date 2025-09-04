<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use Override;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class GraphQLTokenParser extends AbstractTokenParser
{
    #[Override]
    public function parse(Token $token) : GraphQLNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(static fn(Token $token) : bool => $token->test('endgraphql'), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new GraphQLNode($body, $lineno);
    }

    #[Override]
    public function getTag() : string
    {
        return 'graphql';
    }
}
