<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

/**
 * Knows which generated data classes accept a `$hooks` constructor argument
 * and which hook names each one carries.
 *
 * Consulted by `ObjectTypeInitializer` (to forward `$this->hooks` into child
 * constructors) and `OperationClassGenerator` (to emit the root query's
 * hook parameter and shape). Populated once by `PlanExecutor` after planning.
 */
final class ClassHookUsageRegistry
{
    /**
     * Keys are generated class FQCNs (stored as plain strings because they come
     * from DataClassPlan::$fqcn, which is typed string).
     *
     * @var array<string, array<string, true>>
     */
    public array $classHooks = [];

    public function usesHooks(string $fqcn) : bool
    {
        return isset($this->classHooks[$fqcn]);
    }

    /**
     * @return array<string, true>
     */
    public function getHooksForClass(string $fqcn) : array
    {
        return $this->classHooks[$fqcn] ?? [];
    }

    /**
     * Flat union of every hook name referenced by any generated class.
     *
     * @return array<string, true>
     */
    public function getAllUsedHookNames() : array
    {
        $names = [];

        foreach ($this->classHooks as $hooks) {
            $names += $hooks;
        }

        return $names;
    }
}
