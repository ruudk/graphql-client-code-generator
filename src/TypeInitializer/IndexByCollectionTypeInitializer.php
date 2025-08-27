<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Symfony\Component\TypeInfo\Type;

/**
 * @phpstan-import-type CodeLine from CodeGenerator
 * @implements TypeInitializer<IndexByCollectionType>
 */
final class IndexByCollectionTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof IndexByCollectionType;
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
    ) : Generator {
        // Check if this is multi-field indexing (nested IndexByCollectionType)
        if ($type->value instanceof IndexByCollectionType) {
            // Multi-field indexing: use closure with foreach to handle duplicates
            yield '(function() {';
            yield $generator->indent(function () use ($generator, $type, $variable, $delegator) {
                $nestedType = $type->value;

                yield '$result = [];';
                yield sprintf('foreach (%s as $item) {', $variable);

                yield $generator->indent(function () use ($generator, $type, $delegator, $nestedType) {
                    yield from $generator->wrap(
                        sprintf(
                            '$result[$item%s][$item%s] = ',
                            $this->buildKeyAccess($type->indexBy),
                            $this->buildKeyAccess($nestedType->indexBy),
                        ),
                        $delegator($nestedType->value, $generator, '$item'),
                        ';',
                    );
                });
                yield '}';

                yield '';
                yield 'return $result;';
            });
            yield '})()';

            return;
        }

        // Single-field indexing: use array_combine
        yield 'array_combine(';
        yield $generator->indent(function () use ($generator, $type, $variable, $delegator) {
            if (count($type->indexBy) > 1) {
                yield sprintf(
                    'array_map(fn($item) => $item%s, %s),',
                    $this->buildKeyAccess($type->indexBy),
                    $variable,
                );
            } else {
                yield sprintf(
                    'array_column(%s, %s),',
                    $variable,
                    var_export($type->indexBy[0], true),
                );
            }

            yield from $generator->wrap(
                'array_map(fn($item) => ',
                $delegator($type->value, $generator, '$item'),
                sprintf(', %s),', $variable),
            );
        });
        yield ')';
    }

    /**
     * @param list<string> $indexBy
     */
    private function buildKeyAccess(array $indexBy) : string
    {
        return join(array_map(
            fn($key) => sprintf('[%s]', var_export($key, true)),
            $indexBy,
        ));
    }
}
