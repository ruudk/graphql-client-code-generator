<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @extends Type\CollectionType<Type\GenericType<Type\BuiltinType<TypeIdentifier::ARRAY>>>
 */
final class IndexByCollectionType extends Type\CollectionType
{
    public function __construct(
        Type $key,
        Type $value,
        public readonly string $indexBy,
    ) {
        parent::__construct(
            new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                $key,
                $value,
            ),
        );
    }
}
