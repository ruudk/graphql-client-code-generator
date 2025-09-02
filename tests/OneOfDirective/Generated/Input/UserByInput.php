<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Input;

use JsonSerializable;
use Override;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class UserByInput implements JsonSerializable
{
    private function __construct(
        public null|Stringable|string $id = null,
        public null|Stringable|string $email = null,
    ) {}

    public static function createId(Stringable|string $id) : self
    {
        return new self(id: $id);
    }

    public static function createEmail(Stringable|string $email) : self
    {
        return new self(email: $email);
    }

    /**
     * @return array{
     *     'email': null|string,
     *     'id': null|string,
     * }
     */
    #[Override]
    public function jsonSerialize() : array
    {
        return [
            'id' => $this->id !== null ? (string) $this->id : null,
            'email' => $this->email !== null ? (string) $this->email : null,
        ];
    }
}
