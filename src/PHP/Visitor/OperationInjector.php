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

            $param->type = new Name(new Name($this->operations[$node->name->toString()][$param->var->name])->getLast());

            $changed = true;
        }

        if ( ! $changed) {
            return null;
        }

        return $node;
    }
}
