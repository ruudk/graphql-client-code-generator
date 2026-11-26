<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use GraphQL\Language\VisitorRemoveNode;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class ThrowWhenNullRemover
{
    /**
     * @template T of Node
     * @param T $node
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node): Node
    {
        return VisitedNode::of($node, Visitor::visit($node, [
            NodeKind::DIRECTIVE => static function (Node $node): ?VisitorRemoveNode {
                Assert::isInstanceOf($node, DirectiveNode::class);

                if ($node->name->value !== 'throwWhenNull') {
                    return null;
                }

                return Visitor::removeNode();
            },
        ]));
    }
}
