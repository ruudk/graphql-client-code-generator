<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class UnusedFragmentOptimizer
{
    /**
     * @template T of Node
     * @param T $node
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node) : Node
    {
        if ( ! $node instanceof DocumentNode) {
            return $node;
        }

        $usedFragments = UsedFragmentsVisitor::getUsedFragments($node);

        $new = Visitor::visit($node, [
            NodeKind::FRAGMENT_DEFINITION => function (Node $node) use ($usedFragments) {
                Assert::isInstanceOf($node, FragmentDefinitionNode::class);

                if (in_array($node->name->value, $usedFragments, true)) {
                    return null;
                }

                return Visitor::removeNode();
            },
        ]);

        Assert::isInstanceOf($new, Node::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }
}
