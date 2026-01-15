<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Removes stale use statements for generated GraphQL classes.
 *
 * When a file is moved or refactored, the hash in the generated namespace changes.
 * This visitor removes use statements that match the generated namespace pattern
 * but don't match the current valid FQCNs.
 */
final class StaleImportRemover extends NodeVisitorAbstract
{
    /**
     * @var array<string, bool>
     */
    private array $validFqcns;
    private string $namespacePattern;

    /**
     * @param string $generatedNamespace The namespace for generated classes (e.g., "App\Generated")
     * @param list<string> $validFqcns The list of valid/current FQCNs to keep
     */
    public function __construct(
        string $generatedNamespace,
        array $validFqcns,
    ) {
        $this->validFqcns = array_fill_keys($validFqcns, true);

        // Build a regex pattern to match generated operation imports
        // Pattern: {namespace}\{Query|Mutation}\{OperationNameWithHash}\{ClassName}
        // The hash is 6 hex characters
        $escapedNamespace = preg_quote($generatedNamespace, '/');
        $this->namespacePattern = '/^' . $escapedNamespace . '\\\\(?:Query|Mutation)\\\\[A-Za-z]+[a-f0-9]{6}\\\\/';
    }

    #[Override]
    public function enterNode(Node $node) : ?int
    {
        if ( ! $node instanceof Use_) {
            return null;
        }

        // Check each use clause
        foreach ($node->uses as $use) {
            $fqcn = $use->name->toString();

            // If this import matches the generated pattern but is not in our valid list, remove it
            if (preg_match($this->namespacePattern, $fqcn) === 1 && ! isset($this->validFqcns[$fqcn])) {
                return NodeVisitor::REMOVE_NODE;
            }
        }

        return null;
    }
}
