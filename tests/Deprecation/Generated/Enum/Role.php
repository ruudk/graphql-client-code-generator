<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum Role: string
{
    case Admin = 'ADMIN';

    /**
     * Can edit everything except settings
     *
     * @deprecated Use `ADMIN`.
     */
    case Editor = 'EDITOR';

    /**
     * @deprecated No longer supported
     */
    case Viewer = 'VIEWER';
}
