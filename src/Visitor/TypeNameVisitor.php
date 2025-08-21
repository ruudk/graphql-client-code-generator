<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
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
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class TypeNameVisitor
{
    public function __construct(
        private Schema $schema,
    ) {}

    /**
     * @template T of Node
     * @param T $node
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node) : Node
    {
        $typeInfo = new TypeInfo($this->schema);

        $wrapped = Visitor::visitWithTypeInfo($typeInfo, [
            NodeKind::SELECTION_SET => function (Node $node) use ($typeInfo) : ?Node {
                Assert::isInstanceOf($node, SelectionSetNode::class);

                $type = Type::getNamedType($typeInfo->getType());

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

        $new = Visitor::visit($node, $wrapped);

        Assert::isInstanceOf($new, Node::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }
}
