<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

/**
 * A `{% graphql %}` block in a Twig template.
 */
final readonly class GraphQLBlock
{
    /**
     * @param ?string $schema The schema the block names with `{% graphql schema 'billing' %}`, if any
     * @param non-empty-string $graphql
     */
    public function __construct(
        public ?string $schema,
        public string $graphql,
        public int $line,
    ) {}
}
