<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Type\GroupByCollectionType;
use Symfony\Component\TypeInfo\Type;

/**
 * @phpstan-import-type CodeLine from CodeGenerator
 * @implements TypeInitializer<GroupByCollectionType>
 */
final class GroupByCollectionTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type): bool
    {
        return $type instanceof GroupByCollectionType;
    }

    /**
     * @return Generator<CodeLine>
     */
    #[Override]
    public function initialize(
        Type $type,
        CodeGenerator $generator,
        string $variable,
        DelegatingTypeInitializer $delegator,
    ): Generator {
        yield '(function() {';
        yield $generator->indent(function () use ($generator, $type, $variable, $delegator) {
            yield '$result = [];';
            yield sprintf('foreach (%s as $item) {', $variable);
            yield $generator->indent(function () use ($generator, $type, $delegator) {
                yield from $generator->wrap(
                    sprintf('$result[$item%s][] = ', $this->buildKeyAccess($type->groupBy)),
                    $delegator($type->value, $generator, '$item'),
                    ';',
                );
            });
            yield '}';

            yield '';
            yield 'return $result;';
        });
        yield '})()';
    }

    /**
     * @param list<string> $groupBy
     */
    private function buildKeyAccess(array $groupBy): string
    {
        return implode(array_map(static fn($key) => sprintf('[%s]', var_export($key, true)), $groupBy));
    }
}
