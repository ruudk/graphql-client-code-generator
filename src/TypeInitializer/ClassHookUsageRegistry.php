<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Ruudk\GraphQLCodeGenerator\Config\HookDefinition;

/**
 * Knows which generated data classes accept a `$hooks`, `$loaders` and/or
 * `$decoders` constructor argument and which hook names and scalars each one
 * carries.
 *
 * Consulted by `ObjectTypeInitializer` (to forward `$this->hooks` /
 * `$this->loaders` / `$this->decoders` into child constructors) and
 * `OperationClassGenerator` (to emit the root query's parameters and shapes).
 * Populated once by `PlanExecutor` after planning.
 */
final class ClassHookUsageRegistry
{
    /**
     * @param array<string, HookDefinition> $hookDefinitions The registered hooks,
     *                                                       used to tell legacy hooks from batched ones.
     */
    public function __construct(
        private readonly array $hookDefinitions = [],
    ) {}

    /**
     * Keys are generated class FQCNs (stored as plain strings because they come
     * from DataClassPlan::$fqcn, which is typed string).
     *
     * @var array<string, array<string, true>>
     */
    public array $classHooks = [];

    /**
     * True when the class carries at least one legacy (non-batched) hook and
     * therefore needs the `$hooks` constructor argument.
     */
    public function usesLegacyHooks(string $fqcn): bool
    {
        foreach (array_keys($this->getHooksForClass($fqcn)) as $name) {
            if (!($this->hookDefinitions[$name]->batched ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the class carries at least one batched hook and therefore needs
     * the `$loaders` constructor argument.
     */
    public function usesBatchedHooks(string $fqcn): bool
    {
        foreach (array_keys($this->getHooksForClass($fqcn)) as $name) {
            if ($this->hookDefinitions[$name]->batched ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Keys are generated class FQCNs, values the scalars whose decoder the class needs.
     *
     * @var array<string, array<string, true>>
     */
    public array $classDecoders = [];

    /**
     * @return array<string, true>
     */
    public function getHooksForClass(string $fqcn): array
    {
        return $this->classHooks[$fqcn] ?? [];
    }

    /**
     * True when the class decodes a scalar and therefore needs the `$decoders` constructor argument.
     */
    public function usesDecoders(string $fqcn): bool
    {
        return $this->getDecodersForClass($fqcn) !== [];
    }

    /**
     * @return array<string, true>
     */
    public function getDecodersForClass(string $fqcn): array
    {
        return $this->classDecoders[$fqcn] ?? [];
    }

    /**
     * Flat union of every hook name referenced by any generated class.
     *
     * @return array<string, true>
     */
    public function getAllUsedHookNames(): array
    {
        $names = [];

        foreach ($this->classHooks as $hooks) {
            $names += $hooks;
        }

        return $names;
    }
}
