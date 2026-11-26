<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use Override;
use Twig\Error\SyntaxError;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Parses `{% graphql %}...{% endgraphql %}` and `{% graphql schema 'billing' %}...{% endgraphql %}`.
 * The block only holds GraphQL for the generator, so it renders nothing.
 */
final class GraphQLTokenParser extends AbstractTokenParser
{
    /**
     * @throws SyntaxError
     */
    #[Override]
    public function parse(#[\SensitiveParameter] Token $token): GraphQLNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        if ($stream->nextIf(Token::NAME_TYPE, 'schema') !== null) {
            $stream->expect(
                Token::STRING_TYPE,
                null,
                'The schema of a graphql block has to be a string, like {% graphql schema \'billing\' %}',
            );
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(static fn(#[\SensitiveParameter] Token $token): bool => $token->test(
            'endgraphql',
        ), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new GraphQLNode($body, $lineno);
    }

    #[Override]
    public function getTag(): string
    {
        return 'graphql';
    }
}
