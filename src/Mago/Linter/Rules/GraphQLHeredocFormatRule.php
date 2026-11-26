<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago\Linter\Rules;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use JsonException;
use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Reporting\TextEdit;
use Mago\Sdk\Syntax\NodeKind;
use Override;

/**
 * Formats the GraphQL operation inside `<<<'GRAPHQL' ... GRAPHQL` nowdoc
 * strings using webonyx/graphql-php so it follows a single canonical style.
 *
 * The body is indented to the closing `GRAPHQL` marker, so the indentation
 * that Mago's formatter gives the nowdoc is kept as is.
 *
 * @api
 */
final class GraphQLHeredocFormatRule implements Rule
{
    public const string CODE = 'graphql/heredoc-format';

    #[Override]
    public function getDefinition() : RuleDefinition
    {
        return new RuleDefinition(
            code: self::CODE,
            name: 'GraphQL heredoc format',
            description: "Formats GraphQL inside <<<'GRAPHQL' nowdoc strings with the webonyx/graphql-php printer.",
            defaultLevel: Level::Warning,
            defaultEnabled: true,
            targets: [NodeKind::DocumentString],
        );
    }

    #[Override]
    public function lint(LintContext $context) : void
    {
        $text = $context->getText();
        $matches = [];

        // Only single-quoted nowdoc with the GRAPHQL label. A heredoc
        // would interpolate GraphQL `$variables` as PHP variables.
        if (preg_match("/\\A(<<<[ \\t]*'GRAPHQL')\\r?\\n(.*)\\r?\\n([ \\t]*)GRAPHQL\\z/s", $text, $matches) !== 1) {
            return;
        }

        [, $opening, $body, $indent] = $matches;

        try {
            $formatted = rtrim(Printer::doPrint(Parser::parse($body)), "\r\n");
        } catch (JsonException | SyntaxError) {
            return;
        }

        $lines = array_map(static fn($line) => $line === '' ? '' : $indent . $line, explode("\n", $formatted));

        $replacement = $opening . "\n" . implode("\n", $lines) . "\n" . $indent . 'GRAPHQL';

        if ($replacement === $text) {
            return;
        }

        $context->report(Issue::new('GraphQL in this nowdoc is not formatted.', $context->node->span)->withHelp(
            'Format the GraphQL with the webonyx/graphql-php printer.',
        )->withEdit(TextEdit::replace($context->node->span, $replacement)));
    }
}
