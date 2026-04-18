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
    private string $operationClassPattern;

    /**
     * @param string $generatedNamespace The namespace for generated classes (e.g., "App\Generated")
     * @param list<string> $validFqcns The list of valid/current FQCNs to keep
     */
    public function __construct(
        string $generatedNamespace,
        array $validFqcns,
    ) {
        $this->validFqcns = array_fill_keys($validFqcns, true);

        // Match only the operation's leaf classes we generate and can reason about:
        // the Query/Mutation class itself and its optional FailedException. Sibling
        // classes (Data\..., Error\...) intentionally fall outside this pattern so
        // we don't remove them — we have no way to tell if they're stale or still in use.
        $escapedNamespace = preg_quote($generatedNamespace, '/');
        $this->operationClassPattern = '/^' . $escapedNamespace . '\\\\(?:Query|Mutation)\\\\[A-Za-z]+[a-f0-9]{6}\\\\[A-Za-z]+(?:Query|Mutation)(?:FailedException)?$/';
    }

    #[Override]
    public function enterNode(Node $node) : ?int
    {
        if ( ! $node instanceof Use_) {
            return null;
        }

        foreach ($node->uses as $use) {
            $fqcn = $use->name->toString();

            if (preg_match($this->operationClassPattern, $fqcn) === 1 && ! isset($this->validFqcns[$fqcn])) {
                return NodeVisitor::REMOVE_NODE;
            }
        }

        return null;
    }
}
