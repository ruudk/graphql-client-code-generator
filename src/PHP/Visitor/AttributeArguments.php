<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

final readonly class AttributeArguments
{
    public static function find(Node\Attribute $attribute, string $name, int $position) : ?Node\Expr
    {
        foreach ($attribute->args as $index => $arg) {
            if ($arg->name?->toString() === $name) {
                return $arg->value;
            }

            if ($arg->name === null && $index === $position) {
                return $arg->value;
            }
        }

        return null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function scope(Node\Attribute $attribute, string $location) : ?string
    {
        $value = self::find($attribute, 'scope', 1);

        if ($value === null) {
            return null;
        }

        if ($value instanceof Node\Expr\ConstFetch && $value->name->toLowerString() === 'null') {
            return null;
        }

        if ($value instanceof String_) {
            return $value->value;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot read the scope of #[%s] on "%s". Write it as a string, like \'billing\'.',
            $attribute->name->getLast(),
            $location,
        ));
    }
}
