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
final readonly class IndexByCollectionTypeInitializer implements TypeInitializer
{
    public function __construct(
        private ListKeyAccess $keyAccess,
    ) {}

    #[Override]
    public function supports(Type $type): bool
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
    ): Generator {
        // Check if this is multi-field indexing (nested IndexByCollectionType)
        if ($type->value instanceof IndexByCollectionType) {
            // Multi-field indexing: use closure with foreach to handle duplicates
            yield '(function() {';
            yield $generator->indent(function () use ($generator, $type, $variable, $delegator) {
                $nestedType = $type->value;

                yield '$result = [];';
                yield sprintf('foreach (%s as $item) {', $variable);

                yield $generator->indent(function () use ($generator, $type, $delegator, $nestedType) {
                    [$outerKey, $innerKey] = $this->keyAccess->build($generator, '$item', [
                        [$type->indexBy, $type->throwWhenNull],
                        [$nestedType->indexBy, $nestedType->throwWhenNull],
                    ]);

                    yield from $generator->wrap(
                        sprintf('$result[%s][%s] = ', $outerKey, $innerKey),
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
            if (count($type->indexBy) > 1 || $type->throwWhenNull !== []) {
                [$key] = $this->keyAccess->build($generator, '$item', [[$type->indexBy, $type->throwWhenNull]]);

                yield sprintf('array_map(fn($item) => %s, %s),', $key, $variable);
            } else {
                yield sprintf('array_column(%s, %s),', $variable, var_export($type->indexBy[0], true));
            }

            $inner = $delegator($type->value, $generator, '$item');

            if ($inner === '$item') {
                yield sprintf('%s,', $variable);

                return;
            }

            yield from $generator->wrap('array_map(fn($item) => ', $inner, sprintf(', %s),', $variable));
        });
        yield ')';
    }
}
