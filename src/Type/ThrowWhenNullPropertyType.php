<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Override;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;

/**
 * Marker wrapper signalling that a generated property must throw a
 * `NodeNotFoundException` when the underlying payload value is null. The
 * wrapped type is the (already non-nullable) PHP type used for the property
 * declaration.
 *
 * Set by `SelectionSetPlanner` when a field carries `@throwWhenNull`; consumed
 * by `DataClassGenerator` to emit the throwing getter.
 *
 * @implements WrappingTypeInterface<SymfonyType>
 */
final class ThrowWhenNullPropertyType extends SymfonyType implements WrappingTypeInterface
{
    public function __construct(
        private readonly SymfonyType $wrappedType,
    ) {}

    #[Override]
    public function getWrappedType() : SymfonyType
    {
        return $this->wrappedType;
    }

    #[Override]
    public function wrappedTypeIsSatisfiedBy(callable $specification) : bool
    {
        return $specification($this->wrappedType);
    }

    #[Override]
    public function __toString() : string
    {
        return (string) $this->wrappedType;
    }
}
