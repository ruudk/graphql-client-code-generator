<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @implements TypeInitializer<Type\ObjectType<*>>
 */
final class ObjectTypeInitializer implements TypeInitializer
{
    /**
     * @var list<TypeInitializer>
     */
    private array $initializers;
    private ?ClassHookUsageRegistry $hookUsageRegistry = null;

    public function __construct(
        TypeInitializer ...$initializers,
    ) {
        $this->initializers = array_values($initializers);
    }

    public function setHookUsageRegistry(ClassHookUsageRegistry $registry) : self
    {
        $this->hookUsageRegistry = $registry;

        return $this;
    }

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
    ) : Generator | string {
        foreach ($this->initializers as $initializer) {
            if ( ! $initializer->supports($type)) {
                continue;
            }

            return $initializer->initialize($type, $generator, $variable, $delegator);
        }

        $arguments = $this->hookUsageRegistry?->usesHooks($type->getClassName()) === true
            ? sprintf('%s, $this->hooks', $variable)
            : $variable;

        return sprintf('new %s(%s)', $generator->import($type->getClassName()), $arguments);
    }
}
