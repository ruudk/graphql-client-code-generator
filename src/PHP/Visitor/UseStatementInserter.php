<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;

final class UseStatementInserter extends NodeVisitorAbstract
{
    /**
     * @var array<string, bool>
     */
    private array $insertedFqcns = [];

    /**
     * @var list<string>
     */
    private array $fqcnsToInsert;

    /**
     * @param list<string> $fqcns
     */
    public function __construct(
        array $fqcns,
    ) {
        // Remove duplicates and sort the FQCNs
        $this->fqcnsToInsert = array_values(array_unique($fqcns));
        sort($this->fqcnsToInsert, SORT_STRING | SORT_FLAG_CASE);
    }

    /**
     * @return null|array<Node>
     */
    #[Override]
    public function enterNode(Node $node) : ?array
    {
        // Only process Use_ nodes
        if ( ! $node instanceof Use_) {
            return null;
        }

        // Get the current use statement's name
        $currentName = $node->uses[0]->name->toString();

        // Mark this FQCN as already existing if it's in our list
        if (in_array($currentName, $this->fqcnsToInsert, true)) {
            $this->insertedFqcns[$currentName] = true;
        }

        // Collect all FQCNs that should be inserted before this node
        $toInsertBefore = [];
        foreach ($this->fqcnsToInsert as $fqcn) {
            if (isset($this->insertedFqcns[$fqcn])) {
                continue;
            }

            if (strcasecmp($fqcn, $currentName) < 0) {
                $toInsertBefore[] = $fqcn;
                $this->insertedFqcns[$fqcn] = true;
            }
        }

        if ($toInsertBefore !== []) {
            return $this->insertMultipleBefore($node, $toInsertBefore);
        }

        return null;
    }

    /**
     * @return null|array<Node>
     */
    #[Override]
    public function leaveNode(Node $node) : ?array
    {
        if ( ! $node instanceof Use_) {
            return null;
        }

        $currentName = $node->uses[0]->name->toString();

        // Get parent and find next sibling
        $parent = $node->getAttribute('parent');
        $key = $node->getAttribute('key');

        if ( ! is_int($key)) {
            return null;
        }

        // Use statements can be in a Namespace or at the root level
        // By checking the specific parent types, we avoid dynamic property access
        $stmts = null;

        if ($parent instanceof Namespace_) {
            $stmts = $parent->stmts;
        } elseif (is_array($parent)) {
            // Root level - parent is the statements array itself
            $stmts = $parent;
        }

        if ($stmts === null) {
            return null;
        }

        $nextIndex = $key + 1;

        // Determine what FQCNs should be inserted after this node
        $toInsertAfter = [];

        // If there's no next statement or next is not a Use_, insert remaining after current
        if ( ! isset($stmts[$nextIndex]) || ! ($stmts[$nextIndex] instanceof Use_)) {
            // This is the last use statement, insert all remaining that come after
            foreach ($this->fqcnsToInsert as $fqcn) {
                if (isset($this->insertedFqcns[$fqcn])) {
                    continue;
                }

                if (strcasecmp($fqcn, $currentName) > 0) {
                    $toInsertAfter[] = $fqcn;
                    $this->insertedFqcns[$fqcn] = true;
                }
            }
        } else {
            // Check next Use_ statement
            $nextUse = $stmts[$nextIndex];
            $nextName = $nextUse->uses[0]->name->toString();

            // Find FQCNs that should go between current and next
            foreach ($this->fqcnsToInsert as $fqcn) {
                if (isset($this->insertedFqcns[$fqcn])) {
                    continue;
                }

                if (strcasecmp($fqcn, $currentName) > 0 && strcasecmp($fqcn, $nextName) < 0) {
                    $toInsertAfter[] = $fqcn;
                    $this->insertedFqcns[$fqcn] = true;
                }
            }
        }

        if ($toInsertAfter !== []) {
            return $this->insertMultipleAfter($node, $toInsertAfter);
        }

        return null;
    }

    /**
     * @param array<Node> $nodes
     * @return null|array<Node>
     */
    #[Override]
    public function afterTraverse(array $nodes) : ?array
    {
        // Check if there are any FQCNs that haven't been inserted yet
        $remainingFqcns = [];
        foreach ($this->fqcnsToInsert as $fqcn) {
            if ( ! isset($this->insertedFqcns[$fqcn])) {
                $remainingFqcns[] = $fqcn;
            }
        }

        if ($remainingFqcns === []) {
            return null;
        }

        // Find the namespace node and insert remaining FQCNs
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                $this->handleNamespaceNode($node, $remainingFqcns);

                return null;
            }
        }

        return null;
    }

    /**
     * @param list<string> $fqcns
     */
    private function handleNamespaceNode(Namespace_ $namespace, array $fqcns) : void
    {
        $firstNonUseIndex = null;
        $lastUseIndex = null;

        foreach ($namespace->stmts as $index => $stmt) {
            if ($stmt instanceof Use_) {
                $lastUseIndex = (int) $index;
            } elseif ($firstNonUseIndex === null) {
                $firstNonUseIndex = (int) $index;
            }
        }

        // Create use statements for all remaining FQCNs
        $newUses = [];
        foreach ($fqcns as $fqcn) {
            $newUses[] = new Use_([
                new UseUse(new Node\Name($fqcn)),
            ]);
            $this->insertedFqcns[$fqcn] = true;
        }

        // If no use statements exist, insert at the beginning (after namespace)
        if ($lastUseIndex === null) {
            if ($firstNonUseIndex !== null) {
                array_splice($namespace->stmts, 0, 0, $newUses);
            } else {
                $namespace->stmts = array_merge($newUses, $namespace->stmts);
            }
        } else {
            // Insert after the last use statement
            array_splice($namespace->stmts, $lastUseIndex + 1, 0, $newUses);
        }
    }

    /**
     * @param list<string> $fqcns
     * @return array<Node>
     */
    private function insertMultipleBefore(Use_ $node, array $fqcns) : array
    {
        $newUses = [];
        foreach ($fqcns as $fqcn) {
            $newUses[] = new Use_([
                new UseUse(new Node\Name($fqcn)),
            ]);
        }

        return array_merge($newUses, [$node]);
    }

    /**
     * @param list<string> $fqcns
     * @return array<Node>
     */
    private function insertMultipleAfter(Use_ $node, array $fqcns) : array
    {
        $newUses = [$node];
        foreach ($fqcns as $fqcn) {
            $newUses[] = new Use_([
                new UseUse(new Node\Name($fqcn)),
            ]);
        }

        return $newUses;
    }
}
