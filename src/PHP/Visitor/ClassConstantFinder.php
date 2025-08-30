<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use Override;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

final class ClassConstantFinder extends NodeVisitorAbstract
{
    /**
     * @var array<string, String_>
     */
    public private(set) array $constants = [];

    #[Override]
    public function enterNode(Node $node) : null
    {
        if ( ! $node instanceof Node\Stmt\ClassConst) {
            return null;
        }

        foreach ($node->consts as $const) {
            if ( ! $const->value instanceof String_) {
                continue;
            }

            $this->constants[$const->name->toString()] = $const->value;
        }

        return null;
    }
}
