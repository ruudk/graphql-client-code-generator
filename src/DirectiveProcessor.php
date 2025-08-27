<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\StringValueNode;

final class DirectiveProcessor
{
    /**
     * Extract the @indexBy directive field path
     *
     * @param NodeList<DirectiveNode> $directives
     * @return list<list<string>>
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

            $value = $directive->arguments[0]->value->value;

            // Split by comma for multi-field indexing
            $fields = explode(',', $value);
            $result = [];

            foreach ($fields as $field) {
                $field = trim($field);
                $result[] = explode('.', $field);
            }

            return $result;
        }

        return [];
    }
}
