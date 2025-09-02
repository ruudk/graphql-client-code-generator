<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Symfony\Component\TypeInfo\Type as SymfonyType;

final readonly class TypeHelper
{
    /**
     * @param callable(SymfonyType): SymfonyType $callable
     */
    public static function rewrap(SymfonyType $type, callable $callable) : SymfonyType
    {
        if ($type instanceof SymfonyType\NullableType) {
            $result = $callable($type->getWrappedType());

            return new SymfonyType\NullableType($result);
        }

        return $callable($type);
    }
}
