<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Hook
{
    /**
     * @param string $requires A named GraphQL fragment describing the data the hook
     *                         needs, e.g. `fragment RefundApprovalContext on Refund { id }`.
     *                         The fragment name becomes the generated data class the hook
     *                         receives; its type condition is the type the `@hook` field
     *                         may be attached to (an object type, or an interface — then
     *                         any implementer). The generator injects the selection into
     *                         queries automatically; callers just write `@hook(name: ...)`.
     * @param bool $batched When true, the hook is resolved as a batched "hook loader":
     *                      invoked exactly once per operation with every occurrence's
     *                      data object at once, instead of once per object instance. The
     *                      `__invoke` signature must then be
     *                      `__invoke(array $inputs): iterable<int, ...>`.
     */
    public function __construct(
        public string $name,
        public string $requires,
        public bool $batched = false,
    ) {}
}
