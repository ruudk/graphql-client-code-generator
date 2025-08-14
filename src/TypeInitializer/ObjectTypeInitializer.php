<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Override;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @implements TypeInitializer<Type\ObjectType<*>>
 */
final class ObjectTypeInitializer implements TypeInitializer
{
    /**
     * @var array<class-string, TypeInitializer>
     */
    private array $initializers;

    public function __construct(
        TypeInitializer ...$initializers,
    ) {
        $this->initializers = array_combine(
            array_map(fn($initializer) => $initializer->getType(), $initializers),
            $initializers,
        );
    }

    #[Override]
    public function getType() : string
    {
        return ObjectType::class;
    }

    #[Override]
    public function __invoke(Type $type, callable $importer, string $variable, DelegatingTypeInitializer $delegator) : string
    {
        if (isset($this->initializers[$type->getClassName()])) {
            return $this->initializers[$type->getClassName()]($type, $importer, $variable, $delegator);
        }

        return sprintf('new %s(%s)', $importer($type->getClassName()), $variable);
    }
}
