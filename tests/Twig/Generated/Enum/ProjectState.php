<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum ProjectState: string
{
    case Active = 'ACTIVE';
    case Archived = 'ARCHIVED';

    public function isActive() : bool
    {
        return $this === self::Active;
    }

    public function isArchived() : bool
    {
        return $this === self::Archived;
    }
}
