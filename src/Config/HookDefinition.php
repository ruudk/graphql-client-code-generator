<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use GraphQL\Language\AST\FragmentDefinitionNode;
use Symfony\Component\TypeInfo\Type;

final readonly class HookDefinition
{
    /**
     * @param class-string $class
     * @param Type $returnType For a legacy hook, the `__invoke` return type. For a
     *                         batched hook, the value type `V` unwrapped from the
     *                         `iterable<int, V>` return.
     * @param FragmentDefinitionNode $requiresFragment The parsed `requires` fragment —
     *                                                 the data the hook consumes.
     * @param string $requiresClassName The fragment name = the generated data class name.
     * @param string $requiresFqcn FQCN of the generated data class (`{namespace}\Hook\{name}`).
     * @param string $requiresTypeCondition The fragment's `on Type` — the type (object or
     *                                      interface) a `@hook(name: ...)` field may be placed on.
     */
    public function __construct(
        public string $name,
        public string $class,
        public Type $returnType,
        public bool $batched,
        public FragmentDefinitionNode $requiresFragment,
        public string $requiresClassName,
        public string $requiresFqcn,
        public string $requiresTypeCondition,
    ) {}
}
