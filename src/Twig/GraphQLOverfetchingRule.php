<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use Override;
use Ruudk\GraphQLCodeGenerator\Twig\Overfetching\OverfetchingAnalyzer;
use TwigCsFixer\Rules\AbstractRule;
use TwigCsFixer\Token\Token;
use TwigCsFixer\Token\Tokens;

/**
 * Reports GraphQL over-fetching: fields selected in a `{% graphql %}` fragment
 * that are never read back in the Twig template that declared it via
 * `{% types %}`.
 */
final class GraphQLOverfetchingRule extends AbstractRule
{
    private readonly OverfetchingAnalyzer $analyzer;

    /**
     * @var array<string, true>
     */
    private array $analyzed = [];

    public function __construct(
        ?OverfetchingAnalyzer $analyzer = null,
    ) {
        $this->analyzer = $analyzer ?? new OverfetchingAnalyzer();
    }

    #[Override]
    protected function process(int $tokenIndex, Tokens $tokens) : void
    {
        $token = $tokens->get($tokenIndex);

        if ( ! $token->isMatching(Token::BLOCK_NAME_TYPE, 'types')) {
            return;
        }

        $fileName = $token->getFilename();

        if (isset($this->analyzed[$fileName])) {
            return;
        }

        $this->analyzed[$fileName] = true;

        $source = @file_get_contents($fileName);

        if ($source === false) {
            return;
        }

        foreach ($this->analyzer->analyze($source, $fileName) as $message) {
            $this->addError($message, $token);
        }
    }
}
