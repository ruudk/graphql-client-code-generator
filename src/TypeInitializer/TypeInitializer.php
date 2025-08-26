<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type;

/**
 * @phpstan-import-type CodeLine from CodeGenerator
 * @template T of Type = Type
 */
interface TypeInitializer
{
    public function supports(Type $type) : bool;

    /**
     * @param T $type
     * @return string|Generator<CodeLine>
     */
    public function initialize(
        Type $type,
        CodeGenerator $generator,
        string $variable,
        DelegatingTypeInitializer $delegator,
    ) : Generator | string;
}
