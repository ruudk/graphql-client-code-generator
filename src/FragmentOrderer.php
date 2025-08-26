<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Exception;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Visitor;
use RuntimeException;
use Webmozart\Assert\Assert;

final class FragmentOrderer
{
    /**
     * @param array<DocumentNode> $documents
     *
     * @throws Exception
     * @throws RuntimeException
     * @return list<FragmentDefinitionNode> Sorted so that dependencies come first
     */
    public static function orderFragments(array $documents) : array
    {
        // Index all fragments by name and collect their dependencies
        /**
         * @var array<string, FragmentDefinitionNode> $fragments
         */
        $fragments = [];

        /**
         * @var array<string, array<string, true>> $deps  fragmentName => set(depName)
         */
        $deps = [];

        foreach ($documents as $doc) {
            foreach ($doc->definitions as $def) {
                if ( ! $def instanceof FragmentDefinitionNode) {
                    continue;
                }

                $name = $def->name->value;
                $fragments[$name] = $def;
                $deps[$name] = self::collectDeps($def); // set-like array: depName => true
            }
        }

        // Optional: detect references to unknown fragments early
        $unknown = [];
        foreach ($deps as $set) {
            foreach (array_keys($set) as $to) {
                if ( ! isset($fragments[$to])) {
                    $unknown[$to] = true;
                }
            }
        }

        if ($unknown !== []) {
            $list = implode(', ', array_keys($unknown));

            throw new RuntimeException(sprintf('Unknown fragment reference(s): %s', $list));
        }

        // Kahn’s algorithm (topological sort)
        // Compute in-degrees
        $inDegree = array_fill_keys(array_keys($fragments), 0);
        foreach ($deps as $set) {
            foreach (array_keys($set) as $to) {
                $inDegree[$to] ??= 0;
                ++$inDegree[$to];
            }
        }

        // Start with nodes that have no incoming edges (no deps pointing to them)
        $queue = [];
        foreach ($inDegree as $name => $deg) {
            if ($deg === 0) {
                $queue[] = $name;
            }
        }

        $sortedNames = [];
        while (count($queue) > 0) {
            $n = array_shift($queue);
            $sortedNames[] = $n;

            foreach (array_keys($deps[$n] ?? []) as $m) {
                $inDegree[$m] ??= 0;
                --$inDegree[$m];

                if ($inDegree[$m] === 0) {
                    $queue[] = $m;
                }
            }

            // Clear edges from n (not strictly necessary in PHP, but mirrors the algo)
            $deps[$n] = [];
        }

        // If not all fragments were output, we have a cycle
        if (count($sortedNames) !== count($fragments)) {
            // Find one cycle-ish set to report
            $remaining = array_keys(array_filter($inDegree, fn($d) => $d > 0));
            $hint = implode(' → ', $remaining);

            throw new RuntimeException(
                sprintf('Circular dependency detected among fragments (in-degree > 0): %s', $hint),
            );
        }

        // Return in dependency-safe order (dependencies first)
        return array_map(fn($name) => $fragments[$name], $sortedNames);
    }

    /**
     * @throws Exception
     * @return array<string,true> set of fragment names used via spreads
     */
    private static function collectDeps(FragmentDefinitionNode $fragment) : array
    {
        $set = [];
        Visitor::visit($fragment, [
            NodeKind::FRAGMENT_SPREAD => function ($node) use (&$set) {
                Assert::isInstanceOf($node, FragmentSpreadNode::class);

                $set[$node->name->value] = true;

                return null;
            },
        ]);

        return $set;
    }
}
