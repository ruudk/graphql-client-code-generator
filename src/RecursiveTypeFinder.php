<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\Type;
use Webmozart\Assert\Assert;

final readonly class RecursiveTypeFinder
{
    /**
     * @param non-empty-list<string> $indexBy
     */
    public static function find(Type $parent, array $indexBy) : Type
    {
        Assert::isInstanceOf($parent, HasFieldsType::class);

        $field = array_shift($indexBy);

        $type = $parent->getField($field)->getType();

        if ($indexBy !== []) {
            $type = Type::getNamedType($type);
            Assert::notNull($type);

            return self::find($type, $indexBy);
        }

        return $type;
    }
}
