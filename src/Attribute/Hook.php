<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Hook
{
    /**
     * @param bool $batched When true, the hook is resolved as a batched "hook loader":
     *                      invoked exactly once per operation with every occurrence's
     *                      inputs at once, instead of once per object instance. The
     *                      `__invoke` signature must then be
     *                      `__invoke(array $inputs): iterable<int, ...>`.
     */
    public function __construct(
        public string $name,
        public bool $batched = false,
    ) {}
}
