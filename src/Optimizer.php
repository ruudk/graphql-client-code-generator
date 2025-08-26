<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Exception;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Schema;
use Ruudk\GraphQLCodeGenerator\Visitor\DuplicateFieldOptimizer;
use Ruudk\GraphQLCodeGenerator\Visitor\IncludeAndSkipDirectiveOptimizer;
use Ruudk\GraphQLCodeGenerator\Visitor\InlineFragmentOptimizer;
use Ruudk\GraphQLCodeGenerator\Visitor\TypeNameVisitor;
use Ruudk\GraphQLCodeGenerator\Visitor\UnusedFragmentOptimizer;

final readonly class Optimizer
{
    /**
     * @var list<callable(Node): Node>
     */
    private array $visitors;

    public function __construct(
        private Schema $schema,
    ) {
        $this->visitors = [
            // new UnusedFragmentOptimizer(),
            new IncludeAndSkipDirectiveOptimizer(),
            new InlineFragmentOptimizer($this->schema),
            new DuplicateFieldOptimizer(),
            new TypeNameVisitor($this->schema),
        ];
    }

    /**
     * @template T of Node
     * @param T $node
     * @throws Exception
     * @return T
     */
    public function optimize(Node $node) : Node
    {
        foreach ($this->visitors as $visitor) {
            $node = $visitor($node);
        }

        return $node;
    }
}
