<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use Ruudk\GraphQLCodeGenerator\GraphQL\FragmentDefinitionNodeWithSource;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class InlineFragmentOptimizer
{
    public function __construct(
        private Schema $schema,
    ) {}

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
        $typeInfo = new TypeInfo($this->schema);

        // @phpstan-ignore argument.type
        $wrapped = Visitor::visitWithTypeInfo($typeInfo, [
            NodeKind::INLINE_FRAGMENT => [
                'leave' => function (Node $node) use ($typeInfo) : ?NodeList {
                    Assert::isInstanceOf($node, InlineFragmentNode::class);

                    $type = Type::getNamedType($typeInfo->getParentType());

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
