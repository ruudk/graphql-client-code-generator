<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator;

use Override;
use Symfony\Component\TypeInfo\Type;

final class NumericStringType extends Type
{
    #[Override]
    public function __toString() : string
    {
        return 'numeric-string';
    }
}
