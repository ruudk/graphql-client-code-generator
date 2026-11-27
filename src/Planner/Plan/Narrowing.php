<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Plan;

use Symfony\Component\TypeInfo\Type as SymfonyType;

/**
 * Set on the class of a field that selects an interface or union through an
 * inline fragment tagged `@narrow`. The class is generated for the fragment's
 * object type, but it is constructed with the payload of the interface or
 * union: its constructor checks the type and throws when the server returned
 * another one.
 */
final readonly class Narrowing
{
    /**
     * @param list<string> $requiredFields Keys the payload of the narrowed type always has, but the payload of the interface or union may not
     */
    public function __construct(
        public string $typeName,
        public string $ownerTypeName,
        public string $fieldName,
        public SymfonyType $payloadShape,
        public array $requiredFields,
    ) {}
}
