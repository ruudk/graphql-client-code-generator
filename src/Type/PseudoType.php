<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Override;
use Symfony\Component\TypeInfo\Type;

final class PseudoType extends Type
{
    public function __construct(
        private string $type,
    ) {}

    #[Override]
    public function __toString() : string
    {
        return $this->type;
    }
}
