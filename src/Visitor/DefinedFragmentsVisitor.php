<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use Webmozart\Assert\Assert;

final readonly class DefinedFragmentsVisitor
{
    /**
     * @throws Exception
     * @return array<string, FragmentDefinitionNode>
     */
    public static function getDefinedFragments(Node $node) : array
    {
        $fragments = [];

        Visitor::visit($node, [
            NodeKind::FRAGMENT_DEFINITION => function (Node $node) use (&$fragments) {
                Assert::isInstanceOf($node, FragmentDefinitionNode::class);

                Assert::keyNotExists($fragments, $node->name->value, 'Fragment name "%s" is already defined.');

                $fragments[$node->name->value] = $node;

                return null;
            },
        ]);

        return $fragments;
    }
}
