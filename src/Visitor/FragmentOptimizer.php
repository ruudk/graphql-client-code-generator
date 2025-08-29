<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use Exception;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

/**
 * This optimizer will remove unused fragments and add global fragments to the document.
 */
final readonly class FragmentOptimizer
{
    /**
     * @template T of Node
     * @param T $node
     * @param array<string, array{FragmentDefinitionNode, list<string>}> $fragmentDefinitions
     * @throws InvalidArgumentException
     * @throws Exception
     * @return T
     */
    public function __invoke(Node $node, array $fragmentDefinitions) : Node
    {
        if ( ! $node instanceof DocumentNode) {
            return $node;
        }

        $definedFragments = [];
        Visitor::visit($node, [
            NodeKind::FRAGMENT_DEFINITION => function (Node $node) use (&$definedFragments) {
                Assert::isInstanceOf($node, FragmentDefinitionNode::class);

                if (in_array($node->name->value, $definedFragments, true)) {
                    return null;
                }

                $definedFragments[] = $node->name->value;

                return null;
            },
        ]);

        $requiredFragments = [];

        Visitor::visit($node, [
            NodeKind::FRAGMENT_SPREAD => function (Node $node) use ($fragmentDefinitions, $definedFragments, &$requiredFragments) {
                Assert::isInstanceOf($node, FragmentSpreadNode::class);

                if (in_array($node->name->value, $definedFragments, true)) {
                    // Fragment is already defined in document, add it to required list to keep it
                    $requiredFragments[] = $node->name->value;

                    return null;
                }

                if (in_array($node->name->value, $requiredFragments, true)) {
                    return null;
                }

                foreach ($this->getDependencies($node->name->value, $fragmentDefinitions) as $dependency) {
                    $requiredFragments[] = $dependency;
                }

                return null;
            },
        ]);

        $requiredFragments = array_unique($requiredFragments);
        sort($requiredFragments);

        foreach ($requiredFragments as $requiredFragment) {
            // Only add if it's not already defined in the document
            if ( ! in_array($requiredFragment, $definedFragments, true)) {
                $node->definitions[] = $fragmentDefinitions[$requiredFragment][0];
            }
        }

        $new = Visitor::visit($node, [
            NodeKind::FRAGMENT_DEFINITION => function (Node $node) use ($requiredFragments) {
                Assert::isInstanceOf($node, FragmentDefinitionNode::class);

                if (in_array($node->name->value, $requiredFragments, true)) {
                    return null;
                }

                return Visitor::removeNode();
            },
        ]);

        Assert::isInstanceOf($new, DocumentNode::class);
        Assert::isAOf($new, $node::class);

        return $new;
    }

    /**
     * @param array<string, array{FragmentDefinitionNode, list<string>}> $fragmentDefinitions
     * @return list<string>
     */
    private function getDependencies(string $name, array $fragmentDefinitions) : array
    {
        $requiredFragments = [$name];
        foreach ($fragmentDefinitions[$name][1] as $dependency) {
            if (in_array($dependency, $requiredFragments, true)) {
                continue;
            }

            foreach ($this->getDependencies($dependency, $fragmentDefinitions) as $dep) {
                $requiredFragments[] = $dep;
            }
        }

        return array_values(array_unique($requiredFragments));
    }
}
