<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @internal Catch-all fallback owned by `PlanExecutor`. Userland should register
 *           type-specific `TypeInitializer` instances via `Config::withTypeInitializer()`;
 *           they run before this one.
 *
 * @implements TypeInitializer<Type\ObjectType<*>>
 */
final readonly class ObjectTypeInitializer implements TypeInitializer
{
    public function __construct(
        private ClassHookUsageRegistry $hookUsageRegistry,
    ) {}

    #[Override]
    public function supports(Type $type) : bool
    {
        return $type instanceof ObjectType;
    }

    #[Override]
    public function initialize(
        Type $type,
        CodeGenerator $generator,
        string $variable,
        DelegatingTypeInitializer $delegator,
    ) : string {
        $arguments = $this->hookUsageRegistry->usesHooks($type->getClassName())
            ? sprintf('%s, $this->hooks', $variable)
            : $variable;

        return sprintf('new %s(%s)', $generator->import($type->getClassName()), $arguments);
    }
}
