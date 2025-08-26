<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum UserRole: string
{
    case Admin = 'ADMIN';
    case User = 'USER';
    case Guest = 'GUEST';

    public function isAdmin() : bool
    {
        return $this === self::Admin;
    }

    public static function createAdmin() : self
    {
        return self::Admin;
    }

    public function isUser() : bool
    {
        return $this === self::User;
    }

    public static function createUser() : self
    {
        return self::User;
    }

    public function isGuest() : bool
    {
        return $this === self::Guest;
    }

    public static function createGuest() : self
    {
        return self::Guest;
    }
}
