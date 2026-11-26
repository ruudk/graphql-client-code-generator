<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\PHP\Visitor;

use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

final readonly class AttributeArguments
{
    /**
     * The value of the argument passed by this name, or at this position when it
     * is passed positionally.
     */
    public static function find(Node\Attribute $attribute, string $name, int $position): ?Node\Expr
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
     * Reads an optional string argument without loading any class, so it has to be
     * a string literal or a constant of the class the attribute is in.
     *
     * @param array<string, String_> $constants The string constants of that class
     * @throws InvalidArgumentException
     */
    public static function string(
        Node\Attribute $attribute,
        string $name,
        int $position,
        array $constants,
        string $location,
    ): ?string {
        $value = self::find($attribute, $name, $position);

        if ($value === null) {
            return null;
        }

        if ($value instanceof Node\Expr\ConstFetch && $value->name->toLowerString() === 'null') {
            return null;
        }

        if (
            $value instanceof Node\Expr\ClassConstFetch
            && $value->class instanceof Node\Name
            && in_array($value->class->toLowerString(), ['self', 'static'], true)
            && $value->name instanceof Node\Identifier
            && isset($constants[$value->name->toString()])
        ) {
            $value = $constants[$value->name->toString()];
        }

        if ($value instanceof String_) {
            return $value->value;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot read the "%s" argument of #[%s] on "%s". Write it as a string, like \'billing\', or as a string constant of the same class, like self::SCHEMA.',
            $name,
            $attribute->name->getLast(),
            $location,
        ));
    }
}
