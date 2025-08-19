<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Language\AST\Node;

final readonly class Validator
{
    /**
     * @param list<callable(Node): void> $visitors
     */
    public function __construct(
        private array $visitors,
    ) {}

    public function validate(Node $node) : void
    {
        foreach ($this->visitors as $visitor) {
            $visitor($node);
        }
    }
}
