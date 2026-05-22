<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Override;
use Symfony\Component\TypeInfo\Type as SymfonyType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;

/**
 * Marker type attached to a generated property that should be populated by a
 * configured hook at runtime rather than by reading from `$this->data`.
 *
 * The wrapped type is the hook's declared return type (used both for the
 * property's PHP type hint and for dumping any PHPDoc). The extra state
 * (hook name + the hook's `requires` data class) drives code emission in
 * DataClassGenerator.
 *
 * @implements WrappingTypeInterface<SymfonyType>
 */
final class HookPropertyType extends SymfonyType implements WrappingTypeInterface
{
    /**
     * @param string $requiresFqcn FQCN of the generated data class the hook receives
     *                             (built from the hook's `requires` fragment).
     * @param string $requiresClassName Short name of that data class.
     * @param bool $batched When true, the hook is resolved by a batched `HookLoader`
     *                      instead of a per-instance `__invoke` call.
     */
    public function __construct(
        public readonly string $hookName,
        public readonly string $requiresFqcn,
        public readonly string $requiresClassName,
        private readonly SymfonyType $returnType,
        public readonly bool $batched = false,
    ) {}

    #[Override]
    public function getWrappedType() : SymfonyType
    {
        return $this->returnType;
    }

    #[Override]
    public function wrappedTypeIsSatisfiedBy(callable $specification) : bool
    {
        return $specification($this->returnType);
    }

    #[Override]
    public function __toString() : string
    {
        return (string) $this->returnType;
    }
}
