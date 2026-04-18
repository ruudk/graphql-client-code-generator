<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use Symfony\Component\TypeInfo\Type;

final readonly class HookDefinition
{
    /**
     * @param class-string $class
     */
    public function __construct(
        public string $name,
        public string $class,
        public Type $returnType,
    ) {}
}
