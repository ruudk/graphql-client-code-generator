<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Visitor;
use Ruudk\GraphQLCodeGenerator\GraphQL\FragmentDefinitionNodeWithSource;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class DuplicateFieldOptimizer
{
    /**
     * @template T of Node
     * @param T $node
     * @param array<string, array{FragmentDefinitionNodeWithSource, list<string>}> $fragmentDefinitions
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node, array $fragmentDefinitions) : Node
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

                        $name = $selection->alias->value ?? $selection->name->value;

                        if (in_array($name, $selected, true)) {
                            $changed = true;

                            continue;
                        }

                        $list[] = $selection;
                        $selected[] = $name;
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
