<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Exception;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use RuntimeException;
use Ruudk\GraphQLCodeGenerator\Visitor\UsedFragmentsVisitor;

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
        /**
         * @var array<string, FragmentDefinitionNode> $fragments
         */
        $fragments = [];

        /**
         * @var array<string, list<string>> $deps  fragmentName => set(depName)
         */
        $deps = [];

        foreach ($documents as $doc) {
            foreach ($doc->definitions as $def) {
                if ( ! $def instanceof FragmentDefinitionNode) {
                    continue;
                }

                $name = $def->name->value;
                $fragments[$name] = $def;
                $deps[$name] = UsedFragmentsVisitor::getUsedFragments($def);
            }
        }

        // Optional: detect references to unknown fragments early
        $unknown = [];
        foreach ($deps as $set) {
            foreach ($set as $to) {
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
            foreach ($set as $to) {
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

            foreach ($deps[$n] ?? [] as $m) {
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
}
