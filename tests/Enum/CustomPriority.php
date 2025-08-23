<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Enum;

enum CustomPriority : string
{
    case Low = 'LOW';
    case Medium = 'MEDIUM';
    case High = 'HIGH';
    case Urgent = 'URGENT';
    case Unknown = 'UNKNOWN';
}
