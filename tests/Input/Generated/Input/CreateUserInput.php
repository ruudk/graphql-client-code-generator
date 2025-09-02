<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Input\Generated\Input;

use JsonSerializable;
use Override;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class CreateUserInput implements JsonSerializable
{
    public function __construct(
        public Stringable|string $firstName,
        public int $age,
        public null|Stringable|string $lastName = null,
    ) {}

    /**
     * @return array{
     *     'age': int,
     *     'firstName': string,
     *     'lastName': null|string,
     * }
     */
    #[Override]
    public function jsonSerialize() : array
    {
        return [
            'firstName' => (string) $this->firstName,
            'age' => $this->age,
            'lastName' => $this->lastName !== null ? (string) $this->lastName : null,
        ];
    }
}
