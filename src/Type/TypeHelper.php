<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Stringable;
use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class TypeHelper
{
    /**
     * @param callable(SymfonyType): SymfonyType $callable
     */
    public static function rewrap(SymfonyType $type, callable $callable): SymfonyType
    {
        if ($type instanceof SymfonyType\NullableType) {
            $result = $callable($type->getWrappedType());

            return new SymfonyType\NullableType($result);
        }

        return $callable($type);
    }

    /**
     * Whether the generated code takes the value as `string|Stringable`, which it casts to a string before it
     * sends it.
     */
    public static function isStringable(SymfonyType $type): bool
    {
        if ($type instanceof SymfonyType\NullableType) {
            $type = $type->getWrappedType();
        }

        if (!$type instanceof SymfonyType\UnionType) {
            return false;
        }

        foreach ($type->getTypes() as $composedType) {
            if (
                $composedType instanceof SymfonyType\ObjectType
                && $composedType->getClassName() === Stringable::class
            ) {
                return true;
            }
        }

        return false;
    }
}
