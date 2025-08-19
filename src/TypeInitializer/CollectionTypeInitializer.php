<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Type\IndexByCollectionType;
use Symfony\Component\TypeInfo\Type;
use Webmozart\Assert\Assert;

/**
 * @implements TypeInitializer<Type\CollectionType<*>>
 */
final readonly class CollectionTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof Type\CollectionType;
    }

    #[Override]
    public function initialize(
        Type $type,
        CodeGenerator $generator,
        string $variable,
        DelegatingTypeInitializer $delegator,
    ) : Generator {
        Assert::isInstanceOf($type, Type\CollectionType::class);

        if ($type instanceof IndexByCollectionType) {
            yield 'array_combine(';
            yield $generator->indent(function () use ($generator, $type, $variable, $delegator) {
                yield sprintf(
                    'array_column(%s, %s),',
                    $variable,
                    var_export($type->indexBy, true),
                );
                yield from $generator->wrap(
                    'array_map(fn($item) => ',
                    $delegator($type->getCollectionValueType(), $generator, '$item'),
                    sprintf(', %s),', $variable),
                );
            });
            yield ')';

            return;
        }

        yield $generator->wrap(
            'array_map(fn($item) => ',
            $delegator($type->getCollectionValueType(), $generator, '$item'),
            sprintf(', %s)', $variable),
        );
    }
}
