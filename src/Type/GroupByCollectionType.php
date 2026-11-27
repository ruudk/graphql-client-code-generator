<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * A list grouped by a field of its items: `array<key, list<value>>`.
 *
 * @extends Type\CollectionType<Type\GenericType>
 */
final class GroupByCollectionType extends Type\CollectionType
{
    /**
     * @param non-empty-list<string> $groupBy
     */
    public function __construct(
        public readonly Type $key,
        public readonly Type $value,
        public readonly array $groupBy,
    ) {
        parent::__construct(new GenericType(new BuiltinType(TypeIdentifier::ARRAY), $key, Type::list($value)));
    }
}
