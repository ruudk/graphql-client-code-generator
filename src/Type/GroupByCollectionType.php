<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * A list grouped by one or more fields of its items, one nesting level per field:
 * `array<keyA, list<value>>`, `array<keyA, array<keyB, list<value>>>` and so on.
 *
 * @extends Type\CollectionType<Type\GenericType>
 */
final class GroupByCollectionType extends Type\CollectionType
{
    /**
     * @param non-empty-list<Type> $keys The key type of every level, outermost first
     * @param non-empty-list<non-empty-list<string>> $groupBy The field path of every level, outermost first
     */
    public function __construct(
        public readonly array $keys,
        public readonly Type $value,
        public readonly array $groupBy,
    ) {
        parent::__construct(
            new GenericType(
                new BuiltinType(TypeIdentifier::ARRAY),
                $keys[0],
                self::nest(array_slice($keys, 1), Type::list($value)),
            ),
        );
    }

    /**
     * Wraps the value in one `array<key, ...>` level per key, outermost key first.
     *
     * @param list<Type> $keys
     */
    public static function nest(array $keys, Type $value): Type
    {
        foreach (array_reverse($keys) as $key) {
            $value = Type::array($value, $key);
        }

        return $value;
    }
}
