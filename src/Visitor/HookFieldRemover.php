<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use GraphQL\Language\VisitorRemoveNode;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class HookFieldRemover
{
    /**
     * Removes fields that carry an `@hook` directive from the document entirely.
     *
     * The hook field name (e.g. `user`) is not a real field on the parent GraphQL type —
     * it's a generator-only marker — so it must be stripped before schema validation and
     * before the operation is printed for the server.
     *
     * @template T of Node
     * @param T $node
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node) : Node
    {
        $new = Visitor::visit($node, [
            NodeKind::FIELD => function (Node $node) : ?VisitorRemoveNode {
                Assert::isInstanceOf($node, FieldNode::class);

                foreach ($node->directives as $directive) {
                    if ($directive->name->value === 'hook') {
                        return Visitor::removeNode();
                    }
                }

                return null;
            },
        ]);

        Assert::isInstanceOf($new, Node::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }
}
