<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements TypeInitializer<Type\BackedEnumType<*, *>>
 */
final readonly class BackedEnumTypeInitializer implements TypeInitializer
{
    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof Type\BackedEnumType;
    }

    #[Override]
    public function initialize(
        Type $type,
        CodeGenerator $generator,
        string $variable,
        DelegatingTypeInitializer $delegator,
    ) : string {
        return sprintf(
            '%s::from(%s)',
            $generator->import($type->getClassName()),
            $variable,
        );
    }
}
