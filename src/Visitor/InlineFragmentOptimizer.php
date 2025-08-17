<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\TypeInfo;
use Webmozart\Assert\Assert;

final readonly class InlineFragmentOptimizer
{
    public function __construct(
        private TypeInfo $typeInfo,
    ) {}

    /**
     * @template T of Node
     * @param T $node
     * @return T
     */
    public function visit(Node $node) : Node
    {
        // @phpstan-ignore argument.type
        $wrapped = Visitor::visitWithTypeInfo($this->typeInfo, [
            NodeKind::INLINE_FRAGMENT => [
                'leave' => function (Node $node) : ?NodeList {
                    Assert::isInstanceOf($node, InlineFragmentNode::class);

                    $type = Type::getNamedType($this->typeInfo->getParentType());

                    Assert::notNull($type);

                    if ($type->name() !== $node->typeCondition?->name->value) {
                        return null;
                    }

                    return $node->selectionSet->selections;
                },
            ],
        ]);

        $new = Visitor::visit($node, $wrapped);

        Assert::isInstanceOf($new, Node::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }
}
