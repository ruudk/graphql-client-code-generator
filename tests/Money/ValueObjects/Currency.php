<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Money\ValueObjects;

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

    public static function EUR() : self
    {
        return new self('EUR');
    }

    public static function USD() : self
    {
        return new self('USD');
    }

    public static function GBP() : self
    {
        return new self('GBP');
    }

    public function equals(self $other) : bool
    {
        return $this->code === $other->code;
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
