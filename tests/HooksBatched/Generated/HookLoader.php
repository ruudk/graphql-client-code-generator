<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched\Generated;

use Closure;
use WeakMap;

// This file was automatically generated and should not be edited.

/**
 * Batches a `@hook` so the user-supplied hook runs exactly once per
 * operation. The first access of any hooked property triggers a single
 * walk of the typed object graph (via the generated collect* methods).
 * Inputs are de-duplicated by value, the hook is invoked once with the
 * distinct set, and results are distributed back by owning object
 * instance.
 * 
 * @internal
 * @template TInput
 * @template TResult
 */
final class HookLoader
{
    private bool $loaded = false;

    /**
     * @var array<int, TResult>
     */
    private array $results = [];

    /**
     * @var WeakMap<object, int>
     */
    private WeakMap $index;

    /**
     * @param Closure(): iterable<array{object, TInput}> $collect
     * @param Closure(array<int, TInput>): iterable<int, TResult> $hook
     */
    public function __construct(
        private readonly Closure $collect,
        private readonly Closure $hook,
    ) {
        $this->index = new WeakMap();
    }

    /**
     * @return TResult
     */
    public function resolve(object $owner) : mixed
    {
        if ( ! $this->loaded) {
            $this->load();
        }

        return $this->results[$this->index[$owner]];
    }

    private function load() : void
    {
        $this->loaded = true;

        $inputs = [];
        $keys = [];

        foreach (($this->collect)() as [$owner, $input]) {
            $hash = serialize($input);

            if ( ! isset($keys[$hash])) {
                $keys[$hash] = count($inputs);
                $inputs[$keys[$hash]] = $input;
            }

            $this->index[$owner] = $keys[$hash];
        }

        foreach (($this->hook)($inputs) as $key => $result) {
            $this->results[$key] = $result;
        }
    }
}
