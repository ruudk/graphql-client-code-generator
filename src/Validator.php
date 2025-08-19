<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Schema;
use Ruudk\GraphQLCodeGenerator\Validator\IndexByValidator;

final readonly class Validator
{
    /**
     * @var list<callable(Node): void>
     */
    private array $visitors;

    public function __construct(
        private Schema $schema,
        private bool $indexByDirective,
    ) {
        $this->visitors = array_filter([
            $this->indexByDirective ? new IndexByValidator($this->schema) : null,
        ]);
    }

    public function validate(Node $node) : void
    {
        foreach ($this->visitors as $visitor) {
            $visitor($node);
        }
    }
}
