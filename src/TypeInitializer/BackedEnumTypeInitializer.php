<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Override;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements TypeInitializer<Type\BackedEnumType<*, *>>
 */
final readonly class BackedEnumTypeInitializer implements TypeInitializer
{
    #[Override]
    public function getType() : string
    {
        return Type\BackedEnumType::class;
    }

    #[Override]
    public function __invoke(Type $type, callable $importer, string $variable, DelegatingTypeInitializer $delegator) : string
    {
        return sprintf(
            '%s::from(%s)',
            $importer($type->getClassName()),
            $variable,
        );
    }
}
