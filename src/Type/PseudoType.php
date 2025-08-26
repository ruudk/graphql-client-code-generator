<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Type;

use Override;
use Symfony\Component\TypeInfo\Type;

/**
 * Can be used by users to create custom types.
 *
 * @api
 */
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
