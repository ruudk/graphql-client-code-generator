<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\ValueObjects;

use JsonSerializable;
use Override;

final readonly class AccountId implements JsonSerializable
{
    public function __construct(
        public int $id,
    ) {}

    #[Override]
    public function jsonSerialize(): int
    {
        return $this->id;
    }
}
