<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use Webmozart\Assert\Assert;

final readonly class UsedFragmentsVisitor
{
    /**
     * @throws Exception
     * @return list<string>
     */
    public static function getUsedFragments(Node $node) : array
    {
        $usedFragments = [];

        Visitor::visit($node, [
            NodeKind::FRAGMENT_SPREAD => function (Node $node) use (&$usedFragments) {
                Assert::isInstanceOf($node, FragmentSpreadNode::class);

                if (in_array($node->name->value, $usedFragments, true)) {
                    return null;
                }

                $usedFragments[] = $node->name->value;

                return null;
            },
        ]);

        return $usedFragments;
    }
}
