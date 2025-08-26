<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Input;

use JsonSerializable;
use Override;

// This file was automatically generated and should not be edited.

final readonly class UserByInput implements JsonSerializable
{
    private function __construct(
        public ?string $id = null,
        public ?string $email = null,
    ) {}

    public static function createId(string $id) : self
    {
        return new self(id: $id);
    }

    public static function createEmail(string $email) : self
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
            'id' => $this->id,
            'email' => $this->email,
        ];
    }
}
