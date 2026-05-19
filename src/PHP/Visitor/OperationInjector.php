<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

final class OperationInjector extends NodeVisitorAbstract
{
    /**
     * @param array<string, array<string, string>> $operations
     */
    public function __construct(
        private array $operations,
    ) {}

    #[Override]
    public function enterNode(Node $node) : ?Node
    {
        if ( ! $node instanceof Node\Stmt\ClassMethod) {
            return null;
        }

        if ( ! isset($this->operations[$node->name->toString()])) {
            return null;
        }

        $changed = false;

        foreach ($node->params as $param) {
            if ( ! $param->var instanceof Node\Expr\Variable) {
                continue;
            }

            if ( ! is_string($param->var->name)) {
                continue;
            }

            if ( ! isset($this->operations[$node->name->toString()][$param->var->name])) {
                continue;
            }

            // A collection contract (`array`/`iterable $items` with a
            // `@param list<Fragment>` docblock) already states its cardinality;
            // only the import needs maintaining, so leave the declared type and
            // let UseStatementInserter add the class.
            if ($param->type instanceof Node\Identifier
                && in_array(strtolower($param->type->name), ['array', 'iterable'], true)) {
                continue;
            }

            $name = new Name(new Name($this->operations[$node->name->toString()][$param->var->name])->getLast());

            // Preserve an explicitly nullable single contract (`?Fragment $x`).
            $param->type = $param->type instanceof Node\NullableType
                ? new Node\NullableType($name)
                : $name;

            $changed = true;
        }

        if ( ! $changed) {
            return null;
        }

        return $node;
    }
}
