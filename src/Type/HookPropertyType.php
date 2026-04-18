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
 * (hook name + input paths) drives code emission in DataClassGenerator.
 *
 * This is a simple wrapper so that `instanceof HookPropertyType` is the only
 * branch the generator has to add to iterate field types as usual.
 *
 * @implements WrappingTypeInterface<SymfonyType>
 */
final class HookPropertyType extends SymfonyType implements WrappingTypeInterface
{
    /**
     * @param list<string> $inputPaths
     */
    public function __construct(
        public readonly string $hookName,
        public readonly array $inputPaths,
        private readonly SymfonyType $returnType,
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
