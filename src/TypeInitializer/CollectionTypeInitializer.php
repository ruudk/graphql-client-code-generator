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
 * @implements TypeInitializer<Type\CollectionType<*>>
 */
final readonly class CollectionTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof Type\CollectionType;
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
        if ($type instanceof IndexByCollectionType) {
            yield 'array_combine(';
            yield $generator->indent(function () use ($generator, $type, $variable, $delegator) {
                if (count($type->indexBy) > 1) {
                    yield sprintf(
                        'array_map(fn($item) => $item%s, %s),',
                        join(array_map(
                            fn($key) => sprintf('[%s]', var_export($key, true)),
                            $type->indexBy,
                        )),
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
                    $delegator($type->getCollectionValueType(), $generator, '$item'),
                    sprintf(', %s),', $variable),
                );
            });
            yield ')';

            return;
        }

        yield from $generator->wrap(
            'array_map(fn($item) => ',
            $delegator($type->getCollectionValueType(), $generator, '$item'),
            sprintf(', %s)', $variable),
        );
    }
}
