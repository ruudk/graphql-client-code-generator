<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Overfetching;

/**
 * The fetched-data shape of a single generated fragment/data class.
 */
final readonly class Shape
{
    public function __construct(
        public string $fqcn,
        /**
         * The template the class was generated from, taken from
         * `#[Generated(source: ...)]`. A nested object selection shares the
         * parent's source; a fragment spread points at a different template.
         * Equality of `source` between a parent and a property's target is
         * therefore the in-file/spread boundary.
         */
        public ?string $source,
        /**
         * @var array<string, ShapeProperty>
         */
        public array $properties,
    ) {}
}
