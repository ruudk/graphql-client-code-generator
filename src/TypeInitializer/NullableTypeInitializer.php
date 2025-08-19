<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type;

/**
 * @phpstan-import-type CodeLine from CodeGenerator
 * @implements TypeInitializer<Type\NullableType<*>>
 */
final readonly class NullableTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof Type\NullableType;
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
        yield from $generator->wrap(
            sprintf(
                '%s !== null ? ',
                $variable,
            ),
            $delegator($type->getWrappedType(), $generator, $variable),
            ' : null',
        );
    }
}
