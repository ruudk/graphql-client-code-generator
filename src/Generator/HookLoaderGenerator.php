<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Generator;

use Closure;
use Ruudk\CodeGenerator\CodeGenerator;
use WeakMap;

/**
 * Emits the `HookLoader` runtime class into the user's `Generated/` namespace
 * (zero external dependencies, like `NodeNotFoundException`). One `HookLoader`
 * instance per batched hook resolves that hook exactly once per operation.
 */
final class HookLoaderGenerator extends AbstractGenerator
{
    public function generate() : string
    {
        $generator = new CodeGenerator($this->config->namespace);

        return $generator->dumpFile(function () use ($generator) {
            yield $this->dumpHeader();
            yield '';

            yield from $generator->docComment(function () {
                yield 'Batches a `@hook` so the user-supplied hook runs exactly once per';
                yield 'operation. The first access of any hooked property triggers a single';
                yield 'walk of the typed object graph (via the generated collect* methods).';
                yield 'Inputs are de-duplicated by value, the hook is invoked once with the';
                yield 'distinct set, and results are distributed back by owning object';
                yield 'instance.';
                yield '';
                yield '@internal';
                yield '@template-covariant TInput';
                yield '@template-covariant TResult';
            });
            yield 'final class HookLoader';
            yield '{';
            yield $generator->indent(function () use ($generator) {
                yield 'private bool $loaded = false;';
                yield '';
                yield from $generator->docComment('@var array<int, TResult>');
                yield 'private array $results = [];';
                yield '';
                yield from $generator->docComment(sprintf('@var %s<object, int>', $generator->import(WeakMap::class)));
                yield sprintf('private %s $index;', $generator->import(WeakMap::class));
                yield '';
                yield from $generator->docComment(function () use ($generator) {
                    yield sprintf(
                        '@param %s(): iterable<array{object, TInput}> $collect',
                        $generator->import(Closure::class),
                    );
                    yield sprintf(
                        '@param %s(array<int, TInput>): iterable<int, TResult> $hook',
                        $generator->import(Closure::class),
                    );
                });
                yield 'public function __construct(';
                yield $generator->indent(function () use ($generator) {
                    yield sprintf('private readonly %s $collect,', $generator->import(Closure::class));
                    yield sprintf('private readonly %s $hook,', $generator->import(Closure::class));
                });
                yield ') {';
                yield $generator->indent(sprintf('$this->index = new %s();', $generator->import(WeakMap::class)));
                yield '}';
                yield '';
                yield from $generator->docComment('@return TResult');
                yield 'public function resolve(object $owner) : mixed';
                yield '{';
                yield $generator->indent(function () use ($generator) {
                    yield 'if ( ! $this->loaded) {';
                    yield $generator->indent('$this->load();');
                    yield '}';
                    yield '';
                    yield 'return $this->results[$this->index[$owner]];';
                });
                yield '}';
                yield '';
                yield 'private function load() : void';
                yield '{';
                yield $generator->indent(function () use ($generator) {
                    yield '$this->loaded = true;';
                    yield '';
                    yield '$inputs = [];';
                    yield '$keys = [];';
                    yield '';
                    yield 'foreach (($this->collect)() as [$owner, $input]) {';
                    yield $generator->indent(function () use ($generator) {
                        yield '$hash = serialize($input);';
                        yield '';
                        yield 'if ( ! isset($keys[$hash])) {';
                        yield $generator->indent(function () {
                            yield '$keys[$hash] = count($inputs);';
                            yield '$inputs[$keys[$hash]] = $input;';
                        });
                        yield '}';
                        yield '';
                        yield '$this->index[$owner] = $keys[$hash];';
                    });
                    yield '}';
                    yield '';
                    yield 'foreach (($this->hook)($inputs) as $key => $result) {';
                    yield $generator->indent('$this->results[$key] = $result;');
                    yield '}';
                });
                yield '}';
            });
            yield '}';
        });
    }
}
