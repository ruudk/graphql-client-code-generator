<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago\Analyzer\Hooks;

/**
 * @internal
 */
enum RestrictedUsageKind
{
    case ClassName;
    case Instantiation;
    case Method;
    case Property;

    /**
     * The issue code, prefixed by Mago with the plugin identifier.
     */
    public function code() : string
    {
        return match ($this) {
            self::ClassName => 'restricted-class',
            self::Instantiation => 'restricted-instantiation',
            self::Method => 'restricted-method',
            self::Property => 'restricted-property',
        };
    }
}
