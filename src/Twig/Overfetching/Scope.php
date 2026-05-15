<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Overfetching;

/**
 * An immutable map of Twig variable name to {@see Binding}. Threaded through
 * the template tree so `{% for %}`/`{% set %}` aliases resolve to the right
 * generated class.
 */
final readonly class Scope
{
    /**
     * @param array<string, Binding> $bindings
     */
    public function __construct(
        private array $bindings = [],
    ) {}

    public function with(string $name, Binding $binding) : self
    {
        $bindings = $this->bindings;
        $bindings[$name] = $binding;

        return new self($bindings);
    }

    public function get(string $name) : Binding
    {
        return $this->bindings[$name] ?? Binding::unknown();
    }
}
