<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Override;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements TypeInitializer<Type\NullableType<*>>
 */
final readonly class NullableTypeInitializer implements TypeInitializer
{
    #[Override]
    public function getType() : string
    {
        return Type\NullableType::class;
    }

    #[Override]
    public function __invoke(Type $type, callable $importer, string $variable, DelegatingTypeInitializer $delegator) : string
    {
        return sprintf(
            '%s !== null ? %s : null',
            $variable,
            $delegator($type->getWrappedType(), $importer, $variable),
        );
    }
}
