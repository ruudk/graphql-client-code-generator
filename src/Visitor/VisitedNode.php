<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Visitor;

use GraphQL\Language\AST\Node;
use Webmozart\Assert\InvalidArgumentException;

final class VisitedNode
{
    /**
     * Narrows the result of `Visitor::visit()` to the type of the visited node.
     *
     * @template T of Node
     * @param T $node
     * @throws InvalidArgumentException
     * @return T
     */
    public static function of(Node $node, mixed $visited): Node
    {
        return self::ofClass($node::class, $visited);
    }

    /**
     * @template T of Node
     * @param class-string<T> $class
     * @throws InvalidArgumentException
     * @return T
     */
    private static function ofClass(string $class, mixed $visited): Node
    {
        if (!$visited instanceof $class) {
            throw new InvalidArgumentException(sprintf(
                'Expected the visitor to return %s, got %s.',
                $class,
                get_debug_type($visited),
            ));
        }

        return $visited;
    }
}
