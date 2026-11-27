<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture;

use JsonSerializable;
use Override;
use Stringable;

/**
 * An id that only the client can write: its string is the raw id, its JSON the encoded one.
 */
final readonly class UserId implements JsonSerializable, Stringable
{
    public function __construct(
        public int $id,
    ) {}

    #[Override]
    public function jsonSerialize(): string
    {
        return base64_encode(sprintf('User:%d', $this->id));
    }

    #[Override]
    public function __toString(): string
    {
        return (string) $this->id;
    }
}
