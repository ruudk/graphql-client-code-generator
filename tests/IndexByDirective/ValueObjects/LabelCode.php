<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\ValueObjects;

use JsonSerializable;
use Override;
use Stringable;

final readonly class LabelCode implements JsonSerializable, Stringable
{
    public function __construct(
        public string $code,
    ) {}

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->code;
    }

    #[Override]
    public function __toString(): string
    {
        return $this->code;
    }
}
