<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use Symfony\Component\TypeInfo\Type;

final readonly class HookDefinition
{
    /**
     * @param class-string $class
     * @param Type $returnType For a legacy hook, the `__invoke` return type. For a
     *                         batched hook, the value type `V` unwrapped from the
     *                         `iterable<int, V>` return.
     */
    public function __construct(
        public string $name,
        public string $class,
        public Type $returnType,
        public bool $batched = false,
    ) {}
}
