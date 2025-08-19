<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements TypeInitializer<Type\NullableType<*>>
 */
final readonly class NullableTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof Type\NullableType;
    }

    #[Override]
    public function initialize(
        Type $type,
        CodeGenerator $generator,
        string $variable,
        DelegatingTypeInitializer $delegator,
    ) : Generator {
        yield $generator->wrap(
            sprintf(
                '%s !== null ? ',
                $variable,
            ),
            $delegator($type->getWrappedType(), $generator, $variable),
            ' : null',
        );
    }
}
