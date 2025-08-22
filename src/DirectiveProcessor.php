<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\StringValueNode;

final class DirectiveProcessor
{
    /**
     * Check if a field has @include or @skip directive
     *
     * @param NodeList<DirectiveNode> $directives
     */
    public function hasIncludeOrSkipDirective(NodeList $directives) : bool
    {
        foreach ($directives as $directive) {
            if (in_array($directive->name->value, ['include', 'skip'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the @indexBy directive field path
     *
     * @param NodeList<DirectiveNode> $directives
     * @return list<string>
     */
    public function getIndexByDirective(NodeList $directives) : array
    {
        foreach ($directives as $directive) {
            if ($directive->name->value !== 'indexBy') {
                continue;
            }

            if ( ! $directive->arguments[0]->value instanceof StringValueNode) {
                continue;
            }

            return explode('.', $directive->arguments[0]->value->value);
        }

        return [];
    }
}
