<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\StringValueNode;
use InvalidArgumentException;

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
     * @param NodeList<DirectiveNode> $directives
     */
    public function hasThrowWhenNullDirective(NodeList $directives) : bool
    {
        foreach ($directives as $directive) {
            if ($directive->name->value === 'throwWhenNull') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param NodeList<DirectiveNode> $directives
     */
    public function hasDirective(NodeList $directives, string $name) : bool
    {
        foreach ($directives as $directive) {
            if ($directive->name->value === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the @hook directive's `name`. The data a hook needs is declared on the
     * hook class via `#[Hook(requires: ...)]` — the call site is just `@hook(name: "x")`.
     *
     * @param NodeList<DirectiveNode> $directives
     * @throws InvalidArgumentException
     */
    public function getHookDirective(NodeList $directives) : ?string
    {
        foreach ($directives as $directive) {
            if ($directive->name->value !== 'hook') {
                continue;
            }

            $name = null;

            foreach ($directive->arguments as $argument) {
                if ($argument->name->value === 'input') {
                    throw new InvalidArgumentException(
                        'The @hook `input:` argument has been removed. A hook now declares '
                        . 'the data it needs via #[Hook(requires: ...)]; the call site is '
                        . 'just @hook(name: "...").',
                    );
                }

                if ($argument->name->value === 'name' && $argument->value instanceof StringValueNode) {
                    $name = $argument->value->value;
                }
            }

            if ($name === null) {
                continue;
            }

            return $name;
        }

        return null;
    }
}
