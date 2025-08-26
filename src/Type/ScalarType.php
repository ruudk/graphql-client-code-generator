<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Override;
use Symfony\Component\TypeInfo\Type;

final class ScalarType extends Type
{
    #[Override]
    public function __toString() : string
    {
        return 'scalar';
    }
}
