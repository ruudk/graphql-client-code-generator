<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use InvalidArgumentException;
use Override;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Webmozart\Assert\Assert;

/**
 * Marker wrapper for a property lifted onto its class by `@flatten`. The value
 * is not read from `$this->data[$name]` but from a path through one or more
 * flattened objects, e.g. `$this->data['viewer']['account']['email']`.
 *
 * The wrapped type is the PHP type of the generated property: the inner type,
 * made nullable when a flattened object on the path may be null (unless that
 * object carries `@throwWhenNull`).
 *
 * Set by `SelectionSetPlanner` when a field carries `@flatten`; consumed by
 * `DataClassGenerator` to emit the path-reading getter.
 *
 * @implements WrappingTypeInterface<SymfonyType>
 */
final class FlattenedPropertyType extends SymfonyType implements WrappingTypeInterface
{
    private readonly SymfonyType $propertyType;

    /**
     * @param non-empty-list<FlattenSegment> $segments The flattened objects, outermost first
     * @param SymfonyType $innerType The field's type as planned inside the innermost flattened object
     * @param null|string $leafKey The field's response key, or null when the field is a fragment accessor
     *                             that wraps the innermost flattened object itself
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly array $segments,
        public readonly SymfonyType $innerType,
        public readonly ?string $leafKey,
    ) {
        Assert::notEmpty($segments);
        Assert::false($innerType instanceof self, 'Nested flattened types must be merged with prepend()');

        $propertyType = $innerType instanceof ThrowWhenNullPropertyType ? $innerType->getWrappedType() : $innerType;

        foreach ($segments as $segment) {
            if ($segment->nullable && !$segment->throwWhenNull && !$propertyType instanceof SymfonyType\NullableType) {
                $propertyType = SymfonyType::nullable($propertyType);
            }
        }

        $this->propertyType = $propertyType;
    }

    /**
     * Wrap a field planned inside a flattened object. A field that is itself
     * flattened gets the new segment in front of its existing path.
     *
     * @throws InvalidArgumentException
     */
    public static function lift(FlattenSegment $segment, SymfonyType $fieldType, ?string $leafKey): self
    {
        if ($fieldType instanceof self) {
            return new self([$segment, ...$fieldType->segments], $fieldType->innerType, $fieldType->leafKey);
        }

        return new self([$segment], $fieldType, $leafKey);
    }

    /**
     * Whether reading this property can throw `NodeNotFoundException`.
     */
    public function throwsWhenNull(): bool
    {
        if ($this->innerType instanceof ThrowWhenNullPropertyType) {
            return true;
        }

        foreach ($this->segments as $segment) {
            if ($segment->throwWhenNull) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function getWrappedType(): SymfonyType
    {
        return $this->propertyType;
    }

    #[Override]
    public function wrappedTypeIsSatisfiedBy(callable $specification): bool
    {
        return $specification($this->propertyType);
    }

    #[Override]
    public function __toString(): string
    {
        return (string) $this->propertyType;
    }
}
