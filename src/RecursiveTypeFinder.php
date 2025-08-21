<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\Type;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

final readonly class RecursiveTypeFinder
{
    /**
     * @param non-empty-list<string> $indexBy
     *
     * @throws InvalidArgumentException
     * @throws InvariantViolation
     */
    public static function find(Type $parent, array $indexBy) : Type
    {
        Assert::isInstanceOf($parent, HasFieldsType::class);

        $field = array_shift($indexBy);

        $type = $parent->getField($field)->getType();

        if ($indexBy !== []) {
            return self::find(
                Type::getNamedType($type),
                $indexBy,
            );
        }

        return $type;
    }
}
