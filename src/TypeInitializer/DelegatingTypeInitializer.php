<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Symfony\Component\TypeInfo\Type;

final readonly class DelegatingTypeInitializer
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

    /**
     * @param callable(string): string $importer
     */
    public function __invoke(Type $type, callable $importer, string $variable) : string
    {
        return $this->initializers[$type::class]($type, $importer, $variable, $this);
    }
}
