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
     * @return list<string>
     */
    public function find(string $content) : array
    {
        $operations = [];
        $stream = $this->twig->tokenize(new Source($content, ''));

        while ( ! $stream->isEOF()) {
            if ($stream->getCurrent()->test('graphql')) {
                $stream->next();
                $stream->expect(Token::BLOCK_END_TYPE);

                $operation = $stream->expect(Token::TEXT_TYPE)->getValue();

                Assert::stringNotEmpty($operation, 'Expected the graphql operation to be a non-empty string');

                $operations[] = $operation;

                $stream->next();
                $stream->expect(Token::NAME_TYPE);

                continue;
            }

            $stream->next();
        }

        return $operations;
    }
}
