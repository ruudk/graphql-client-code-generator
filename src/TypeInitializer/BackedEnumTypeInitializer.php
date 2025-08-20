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
    public function __construct(
        private bool $addUnknownCaseToEnums,
        private string $namespace,
    ) {}

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
        if ($this->addUnknownCaseToEnums && str_starts_with($type->getClassName(), $this->namespace)) {
            return sprintf(
                '%s::tryFrom(%s) ?? %s::Unknown__',
                $generator->import($type->getClassName()),
                $variable,
                $generator->import($type->getClassName()),
            );
        }

        return sprintf(
            '%s::from(%s)',
            $generator->import($type->getClassName()),
            $variable,
        );
    }
}
