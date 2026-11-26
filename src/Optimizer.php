<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Exception;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Schema;
use Ruudk\GraphQLCodeGenerator\GraphQL\FragmentDefinitionNodeWithSource;
use Ruudk\GraphQLCodeGenerator\Visitor\DuplicateFieldOptimizer;
use Ruudk\GraphQLCodeGenerator\Visitor\FragmentOptimizer;
use Ruudk\GraphQLCodeGenerator\Visitor\IncludeAndSkipDirectiveOptimizer;
use Ruudk\GraphQLCodeGenerator\Visitor\InlineFragmentOptimizer;
use Ruudk\GraphQLCodeGenerator\Visitor\TypeNameVisitor;
use Ruudk\GraphQLCodeGenerator\Visitor\VisitedNode;
use Webmozart\Assert\InvalidArgumentException;

final readonly class Optimizer
{
    /**
     * @var list<callable(Node, array<string, array{FragmentDefinitionNodeWithSource, list<string>}>): Node>
     */
    private array $visitors;

    public function __construct(
        private Schema $schema,
    ) {
        $this->visitors = [
            new FragmentOptimizer(),
            new IncludeAndSkipDirectiveOptimizer(),
            new InlineFragmentOptimizer($this->schema),
            new DuplicateFieldOptimizer(),
            new TypeNameVisitor($this->schema),
        ];
    }

    /**
     * @template T of Node
     * @param T $node
     * @param array<string, array{FragmentDefinitionNodeWithSource, list<string>}> $fragmentDefinitions
     * @throws Exception
     * @throws InvalidArgumentException
     * @return T
     */
    public function optimize(Node $node, array $fragmentDefinitions): Node
    {
        foreach ($this->visitors as $visitor) {
            $node = VisitedNode::of($node, $visitor($node, $fragmentDefinitions));
        }

        return $node;
    }
}
