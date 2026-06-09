<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Config;

use Symfony\Component\TypeInfo\Type;

/**
 * An extra, non-GraphQL parameter injected into a generated operation's
 * execute()/executeOrThrow() methods and forwarded positionally to the
 * client's graphql() call.
 */
final readonly class OperationArgument
{
    /**
     * @param null|string $directive When set, the argument only applies to operations carrying this directive.
     *                               When null, it applies to every operation whose type is in $operations.
     * @param list<'query'|'mutation'> $operations The operation types this argument may target.
     */
    public function __construct(
        public string $name,
        public Type $type,
        public ?string $directive,
        public array $operations,
    ) {}
}
