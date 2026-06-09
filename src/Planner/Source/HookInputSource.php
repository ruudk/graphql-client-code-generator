<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner\Source;

/**
 * Source of a generated class that backs a hook's `requires` fragment — the typed
 * data object a `@hook` field hands to the hook. Unlike the other sources this is
 * not tied to a `.graphql` file, Twig template or PHP attribute site: it is
 * derived once from a hook registered via `Config::withHook()`.
 */
final readonly class HookInputSource
{
    /**
     * @param class-string $hookClass
     */
    public function __construct(
        public string $hookClass,
        public string $hookName,
    ) {}
}
