<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

/**
 * One flattened object on the way from a class's `$data` to a lifted field.
 *
 * `key` is the response key of the flattened field, `ownerTypeName` the GraphQL
 * type that declares it and `objectTypeName` the GraphQL type of the flattened
 * object itself (used in `NodeNotFoundException` messages).
 */
final readonly class FlattenSegment
{
    public function __construct(
        public string $key,
        public string $ownerTypeName,
        public string $objectTypeName,
        public bool $nullable,
        public bool $throwWhenNull,
    ) {}
}
