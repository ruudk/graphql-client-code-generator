<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\EnumDumpIsMethods\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum State: string
{
    case Active = 'ACTIVE';

    // When the server returns an unknown enum value, this is the value that will be used.
    case Unknown__ = 'unknown__';

    public function isActive() : bool
    {
        return $this === self::Active;
    }
}
