<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\ListValueNode;
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

    /**
     * Extract the @hook directive's `name` and `input` arguments.
     *
     * @param NodeList<DirectiveNode> $directives
     * @return null|array{name: string, input: list<string>}
     */
    public function getHookDirective(NodeList $directives) : ?array
    {
        foreach ($directives as $directive) {
            if ($directive->name->value !== 'hook') {
                continue;
            }

            $name = null;
            $input = [];

            foreach ($directive->arguments as $argument) {
                if ($argument->name->value === 'name' && $argument->value instanceof StringValueNode) {
                    $name = $argument->value->value;

                    continue;
                }

                if ($argument->name->value === 'input' && $argument->value instanceof ListValueNode) {
                    foreach ($argument->value->values as $value) {
                        if ($value instanceof StringValueNode) {
                            $input[] = $value->value;
                        }
                    }
                }
            }

            if ($name === null) {
                continue;
            }

            return [
                'name' => $name,
                'input' => $input,
            ];
        }

        return null;
    }
}
