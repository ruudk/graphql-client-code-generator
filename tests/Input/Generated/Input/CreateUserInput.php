<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Input\Generated\Input;

use JsonSerializable;
use Override;

// This file was automatically generated and should not be edited.

final readonly class CreateUserInput implements JsonSerializable
{
    public function __construct(
        public string $firstName,
        public int $age,
        public ?string $lastName = null,
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
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'age' => $this->age,
        ];
    }
}
