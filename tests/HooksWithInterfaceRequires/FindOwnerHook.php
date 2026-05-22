<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksWithInterfaceRequires\Generated\Hook\NodeId;

/**
 * The `requires` fragment's type condition is the `Node` interface, so a single
 * hook (and a single generated data class) serves every type implementing it —
 * here both `Article` and `Video`.
 */
#[Hook(
    name: 'findOwner',
    requires: <<<'GRAPHQL'
        fragment NodeId on Node {
          id
        }
        GRAPHQL,
)]
final readonly class FindOwnerHook
{
    /**
     * @param array<string, Owner> $owners
     */
    public function __construct(
        private array $owners = [],
    ) {}

    public function __invoke(NodeId $node) : ?Owner
    {
        return $this->owners[$node->id] ?? null;
    }
}
