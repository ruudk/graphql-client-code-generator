<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Override;
use Symfony\Component\TypeInfo\Type;

final class ArrayTupleType extends Type
{
    /**
     * @param list<Type> $elements
     */
    public function __construct(
        public readonly array $elements,
    ) {}

    #[Override]
    public function __toString() : string
    {
        return sprintf('array{%s}', implode(', ', array_map(
            fn(Type $element) => (string) $element,
            $this->elements,
        )));
    }
}
