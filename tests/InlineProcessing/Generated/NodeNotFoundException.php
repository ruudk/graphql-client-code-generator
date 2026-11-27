<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated;

use Exception;

// This file was automatically generated and should not be edited.

final class NodeNotFoundException extends Exception
{
    public static function create(string $node, string $property) : self
    {
        return new self(sprintf('Field %s.%s is null', $node, $property));
    }

    public static function unexpectedType(string $node, string $property, string $expected, string $actual) : self
    {
        return new self(sprintf('Field %s.%s is %s, expected %s', $node, $property, $actual, $expected));
    }
}
