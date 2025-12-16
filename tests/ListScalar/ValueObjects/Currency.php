<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListScalar\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Override;
use Stringable;

final readonly class Currency implements JsonSerializable, Stringable
{
    public function __construct(
        public string $code,
    ) {
        if (strlen($code) !== 3) {
            throw new InvalidArgumentException('Currency code must be 3 characters');
        }
    }

    #[Override]
    public function jsonSerialize() : string
    {
        return $this->code;
    }

    #[Override]
    public function __toString() : string
    {
        return $this->code;
    }
}
