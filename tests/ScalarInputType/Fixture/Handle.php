<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture;

use JsonSerializable;
use Override;
use Stringable;

/**
 * A handle that only the client can write: its string has an @ in front of it, its JSON does not.
 */
final readonly class Handle implements JsonSerializable, Stringable
{
    public function __construct(
        public string $handle,
    ) {}

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->handle;
    }

    #[Override]
    public function __toString(): string
    {
        return '@' . $this->handle;
    }
}
