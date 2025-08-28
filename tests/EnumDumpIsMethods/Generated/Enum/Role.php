<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\EnumDumpIsMethods\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum Role: string
{
    case User = 'USER';
    case Admin = 'ADMIN';

    // When the server returns an unknown enum value, this is the value that will be used.
    case Unknown__ = 'unknown__';

    public function isUser() : bool
    {
        return $this === self::User;
    }

    public function isAdmin() : bool
    {
        return $this === self::Admin;
    }
}
