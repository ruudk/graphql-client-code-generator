<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Attribute;

use Attribute;

#[Attribute(flags: Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class GeneratedGraphQLFragment
{
    /**
     * @param ?string $schema The name of the schema this belongs to, as set with
     *                        SchemaConfig::create(name: ...). Required when more than one
     *                        schema reads the file, optional otherwise. Write it as a string
     *                        or as a constant of the same class.
     */
    public function __construct(
        public string $fragment,
        public ?string $schema = null,
    ) {}
}
