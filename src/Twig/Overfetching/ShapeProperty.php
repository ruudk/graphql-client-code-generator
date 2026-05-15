<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Overfetching;

/**
 * A single public property of a generated fragment/data class.
 */
final readonly class ShapeProperty
{
    public function __construct(
        public string $name,
        /**
         * Whether the property is tagged `@api` in its phpdoc. The generator
         * uses `@api` to mark properties that are intentionally not read back
         * (e.g. `__typename`-only selections, mutation side-effects), so they
         * must never be reported as over-fetched.
         */
        public bool $api,
        /**
         * Whether the property maps to an actually fetched GraphQL field. Only
         * properties whose getter reads `$this->data` are real fields; derived
         * helpers like `*OrThrow` or `is<Fragment>` reference other properties
         * and never cause any extra data to be fetched.
         */
        public bool $fetchedField,
        /**
         * The FQCN of the generated class this property resolves to (an object
         * selection or a fragment spread), or `null` for scalar/enum leaves.
         */
        public ?string $targetFqcn,
        /**
         * Whether the property is a list (`list<Target>`). Relevant when the
         * property is iterated with `{% for %}`.
         */
        public bool $list,
    ) {}
}
