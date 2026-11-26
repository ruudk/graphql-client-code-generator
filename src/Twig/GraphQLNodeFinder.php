<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use InvalidArgumentException;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Source;
use Twig\Token;
use Webmozart\Assert\Assert;

final readonly class GraphQLNodeFinder
{
    public function __construct(
        private Environment $twig,
    ) {}

    /**
     * @throws SyntaxError
     * @throws InvalidArgumentException
     * @return list<GraphQLBlock>
     */
    public function find(string $content, string $name = ''): array
    {
        $blocks = [];
        $stream = $this->twig->tokenize(new Source($content, $name));

        while (!$stream->isEOF()) {
            if ($stream->getCurrent()->test(Token::NAME_TYPE, 'graphql')) {
                $line = $stream->getCurrent()->getLine();
                $stream->next();

                $schema = null;

                if ($stream->nextIf(Token::NAME_TYPE, 'schema') !== null) {
                    $schema = $stream
                        ->expect(
                            Token::STRING_TYPE,
                            null,
                            'The schema of a graphql block has to be a string, like {% graphql schema \'billing\' %}',
                        )
                        ->getValue();

                    Assert::string($schema);
                }

                $stream->expect(Token::BLOCK_END_TYPE);

                $graphql = $stream->expect(Token::TEXT_TYPE)->getValue();

                Assert::stringNotEmpty($graphql, 'Expected the graphql operation to be a non-empty string');

                $blocks[] = new GraphQLBlock($schema, $graphql, $line);

                $stream->next();
                $stream->expect(Token::NAME_TYPE);

                continue;
            }

            $stream->next();
        }

        return $blocks;
    }
}
