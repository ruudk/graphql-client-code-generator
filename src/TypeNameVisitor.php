<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NameNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\SelectionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Visitor;
use Webmozart\Assert\Assert;

final readonly class TypeNameVisitor
{
    public static function visit(Node $node) : void
    {
        Visitor::visit($node, [
            NodeKind::SELECTION_SET => function (Node $node) : ?Node {
                Assert::isInstanceOf($node, SelectionSetNode::class);

                $needsTypeName = false;
                foreach ($node->selections as $selection) {
                    if ($selection instanceof FieldNode && $selection->name->value === '__typename') {
                        return null;
                    }

                    if ($selection instanceof InlineFragmentNode || $selection instanceof FragmentSpreadNode) {
                        $needsTypeName = true;
                    }
                }

                if ( ! $needsTypeName) {
                    return null;
                }

                /**
                 * @var NodeList<SelectionNode&Node> $selections
                 */
                $selections = new NodeList([
                    new FieldNode([
                        'name' => new NameNode([
                            'value' => '__typename',
                        ]),
                    ]),
                ]);

                $node->selections = $selections->merge($node->selections);

                return $node;
            },
        ]);
    }
}
