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
final readonly class GroupByCollectionTypeInitializer implements TypeInitializer
{
    public function __construct(
        private ListKeyAccess $keyAccess,
    ) {}

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
                    sprintf('$result%s[] = ', $this->buildGroupAccess($generator, $type)),
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
     */
    private function buildGroupAccess(CodeGenerator $generator, GroupByCollectionType $type): string
    {
        $keys = $this->keyAccess->build(
            $generator,
            '$item',
            array_map(
                static fn($level, $fieldPath) => [$fieldPath, $type->throwWhenNull[$level] ?? []],
                array_keys($type->groupBy),
                $type->groupBy,
            ),
        );

        return implode(array_map(static fn($key) => sprintf('[%s]', $key), $keys));
    }
}
