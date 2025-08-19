<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\TypeInitializer;

use Generator;
use Ruudk\CodeGenerator\CodeGenerator;
use Symfony\Component\TypeInfo\Type;

/**
 * @phpstan-import-type CodeLine from CodeGenerator
 */
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
            array_map(fn(TypeInitializer $initializer) => $initializer::class, $initializers),
            $initializers,
        );
    }

    /**
     * @return string|Generator<CodeLine>
     */
    public function __invoke(
        Type $type,
        CodeGenerator $generator,
        string $variable,
    ) : Generator | string {
        foreach ($this->initializers as $initializer) {
            if ( ! $initializer->supports($type)) {
                continue;
            }

            return $initializer->initialize($type, $generator, $variable, $this);
        }

        return $variable;
    }
}
