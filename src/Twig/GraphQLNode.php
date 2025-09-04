<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use Override;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeCaptureInterface;

#[YieldReady]
final class GraphQLNode extends Node implements NodeCaptureInterface
{
    public function __construct(
        Node $body,
        int $lineno,
    ) {
        parent::__construct([
            'body' => $body,
        ], [], $lineno);
    }

    #[Override]
    public function compile(Compiler $compiler) : void
    {
        $compiler->addDebugInfo($this);
    }
}
