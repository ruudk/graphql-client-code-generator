<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Visitor;
use Webmozart\Assert\Assert;

final readonly class DuplicateFieldOptimizer
{
    /**
     * @template T of Node
     * @param T $node
     * @return T
     */
    public function visit(Node $node) : Node
    {
        $new = Visitor::visit($node, [
            NodeKind::SELECTION_SET => [
                'enter' => function (Node $node) {
                    Assert::isInstanceOf($node, SelectionSetNode::class);

                    $selected = [];
                    $list = [];
                    $changed = false;
                    foreach ($node->selections as $selection) {
                        if ( ! $selection instanceof FieldNode) {
                            $list[] = $selection;

                            continue;
                        }

                        if ($selection->alias !== null) {
                            $list[] = $selection;

                            continue;
                        }

                        if (in_array($selection->name->value, $selected, true)) {
                            $changed = true;

                            continue;
                        }

                        $list[] = $selection;
                        $selected[] = $selection->name->value;
                    }

                    if ( ! $changed) {
                        return null;
                    }

                    // @phpstan-ignore assign.propertyType
                    $node->selections = new NodeList($list);

                    return $node;
                },
            ],
        ]);

        Assert::isInstanceOf($new, Node::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }
}
