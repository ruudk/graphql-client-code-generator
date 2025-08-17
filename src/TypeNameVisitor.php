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
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\TypeInfo;
use Webmozart\Assert\Assert;

final readonly class TypeNameVisitor
{
    public function __construct(
        private TypeInfo $typeInfo,
    ) {}

    public function visit(Node $node) : void
    {
        $wrapped = Visitor::visitWithTypeInfo($this->typeInfo, [
            NodeKind::SELECTION_SET => function (Node $node) : ?Node {
                Assert::isInstanceOf($node, SelectionSetNode::class);

                $type = Type::getNamedType($this->typeInfo->getType());

                if ($type instanceof ObjectType) {
                    return null;
                }

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

        Visitor::visit($node, $wrapped);
    }
}
