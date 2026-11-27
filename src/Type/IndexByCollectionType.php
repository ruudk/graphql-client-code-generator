<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @extends Type\CollectionType<Type\GenericType>
 */
final class IndexByCollectionType extends Type\CollectionType
{
    /**
     * @param non-empty-list<string> $indexBy
     * @param array<int, string> $throwWhenNull The type owning each nullable field of the path that is
     *                                          selected with `@throwWhenNull`, by its index in the path
     */
    public function __construct(
        public readonly Type $key,
        public readonly Type $value,
        public readonly array $indexBy,
        public readonly array $throwWhenNull = [],
    ) {
        parent::__construct(new GenericType(new BuiltinType(TypeIdentifier::ARRAY), $key, $value));
    }
}
