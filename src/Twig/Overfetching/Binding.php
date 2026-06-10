<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Overfetching;

/**
 * What a Twig variable currently points at: a generated class (and whether it
 * is a list of them), or nothing we can resolve (`fqcn === null`).
 */
final readonly class Binding
{
    public function __construct(
        public ?string $fqcn = null,
        public bool $list = false,
    ) {}

    public static function unknown() : self
    {
        return new self();
    }

    /**
     * The binding for the element produced when iterating this binding with
     * `{% for %}`.
     */
    public function element() : self
    {
        return new self($this->fqcn, false);
    }
}
