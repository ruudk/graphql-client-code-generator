<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpMethods\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum ProjectStatus: string
{
    case Active = 'ACTIVE';
    case Archived = 'ARCHIVED';
    case Draft = 'DRAFT';

    public function isActive() : bool
    {
        return $this === self::Active;
    }

    public static function createActive() : self
    {
        return self::Active;
    }

    public function isArchived() : bool
    {
        return $this === self::Archived;
    }

    public static function createArchived() : self
    {
        return self::Archived;
    }

    public function isDraft() : bool
    {
        return $this === self::Draft;
    }

    public static function createDraft() : self
    {
        return self::Draft;
    }
}
