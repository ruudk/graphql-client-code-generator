<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Mago\Analyzer\Hooks;

use Mago\Sdk\Syntax\Node;

/**
 * @internal
 */
final readonly class RestrictedUsage
{
    public function __construct(
        public RestrictedUsageKind $kind,
        public Node $node,
        public string $class,
        public ?string $member = null,
    ) {}

    public function describe() : string
    {
        $class = ltrim($this->class, '\\');

        return match ($this->kind) {
            RestrictedUsageKind::ClassName => sprintf('Class %s', $class),
            RestrictedUsageKind::Instantiation => sprintf('Instantiation of %s', $class),
            RestrictedUsageKind::Method => sprintf('Method %s from %s', $this->member, $class),
            RestrictedUsageKind::Property => sprintf('Property %s from %s', $this->member, $class),
        };
    }
}
