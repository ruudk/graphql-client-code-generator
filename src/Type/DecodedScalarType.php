<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Ruudk\GraphQLCodeGenerator\Config\ScalarDecoder;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * The PHP type of a scalar that the server answers and a decoder turns into an object. It is the
 * object type for the property, and tells the initializer which decoder to call and whether to pass
 * the class, which it does for a field with a narrower type.
 *
 * @extends ObjectType<class-string>
 */
final class DecodedScalarType extends ObjectType
{
    /**
     * @param class-string $className
     */
    public function __construct(
        string $className,
        public readonly ScalarDecoder $decoder,
        public readonly bool $narrowed,
    ) {
        parent::__construct($className);
    }
}
