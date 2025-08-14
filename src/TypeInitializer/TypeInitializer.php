<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Symfony\Component\TypeInfo\Type;

/**
 * @template T of Type = Type
 */
interface TypeInitializer
{
    /**
     * @return class-string
     */
    public function getType() : string;

    /**
     * @param T $type
     * @param callable(string): string $importer
     */
    public function __invoke(Type $type, callable $importer, string $variable, DelegatingTypeInitializer $delegator) : string;
}
