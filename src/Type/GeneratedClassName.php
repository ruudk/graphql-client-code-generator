<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use BackedEnum;

final class GeneratedClassName
{
    /**
     * Marks the name of a class that is about to be generated as a class string.
     *
     * Symfony TypeInfo expects class strings, but generated classes do not
     * exist yet while the code generator plans them.
     *
     * @return class-string
     */
    public static function of(string $fqcn): string
    {
        /** @var class-string $fqcn */
        return $fqcn;
    }

    /**
     * @return class-string<BackedEnum>
     */
    public static function ofBackedEnum(string $fqcn): string
    {
        /** @var class-string<BackedEnum> $fqcn */
        return $fqcn;
    }
}
