<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig;

use Override;
use Twig\Extension\AbstractExtension;

final class GraphQLExtension extends AbstractExtension
{
    #[Override]
    public function getTokenParsers() : array
    {
        return [
            new GraphQLTokenParser(),
        ];
    }
}
