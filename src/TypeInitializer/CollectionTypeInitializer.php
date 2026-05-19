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
final class CollectionTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof Type\CollectionType && ! $type instanceof IndexByCollectionType;
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
        $inner = $delegator($type->getCollectionValueType(), $generator, '$item');

        if ($inner === '$item') {
            yield $variable;

            return;
        }

        yield from $generator->wrap(
            'array_map(fn($item) => ',
            $inner,
            sprintf(', %s)', $variable),
        );
    }
}
