<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Override;
use Symfony\Component\TypeInfo\Type;

final class StringLiteralType extends Type
{
    public function __construct(
        private string $type,
    ) {}

    #[Override]
    public function __toString() : string
    {
        return var_export($this->type, true);
    }
}
