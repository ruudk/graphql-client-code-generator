<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Symfony\Component\TypeInfo\Type;

final readonly class SymfonyType
{
    public static function maybeNullbale(bool $condition, Type $type) : Type
    {
        if ($condition) {
            return Type::nullable($type);
        }

        return $type;
    }
}
