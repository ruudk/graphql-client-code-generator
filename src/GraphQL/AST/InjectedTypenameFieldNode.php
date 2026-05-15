<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GraphQL\AST;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\NameNode;

/**
 * A `__typename` selection that the generator injected automatically (see
 * TypeNameVisitor) so it can discriminate interface/union types at runtime.
 *
 * It prints and validates exactly like a normal `__typename` field, but the
 * planner uses the distinct type to tell it apart from a `__typename` the
 * user explicitly selected: injected ones back the internal discrimination
 * only (read via `$this->data['__typename']`) and must NOT surface as a
 * public property, whereas a user-selected `__typename` does.
 */
final class InjectedTypenameFieldNode extends FieldNode
{
    public static function create() : self
    {
        return new self([
            'name' => new NameNode([
                'value' => '__typename',
            ]),
        ]);
    }
}
