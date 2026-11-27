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
                    sprintf('$result%s[] = ', $this->buildGroupAccess($type->groupBy)),
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
     * One `[$item['a']['b']]` per level, so the item lands in the group of every level at once.
     *
     * @param list<list<string>> $groupBy
     */
    private function buildGroupAccess(array $groupBy): string
    {
        return implode(array_map(fn($fieldPath) => sprintf('[$item%s]', $this->buildKeyAccess($fieldPath)), $groupBy));
    }

    /**
     * @param list<string> $fieldPath
     */
    private function buildKeyAccess(array $fieldPath): string
    {
        return implode(array_map(static fn($key) => sprintf('[%s]', var_export($key, true)), $fieldPath));
    }
}
