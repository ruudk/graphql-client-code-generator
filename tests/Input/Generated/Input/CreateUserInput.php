<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Input\Generated\Input;

use JsonSerializable;
use Override;
use Stringable;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
final readonly class CreateUserInput implements JsonSerializable
{
    /**
     * @param null|list<string> $tags
     */
    public function __construct(
        public Stringable|string $firstName,
        public int $age,
        public null|Stringable|string $lastName = null,
        public ?array $tags = null,
    ) {}

    /**
     * @return array{
     *     'age': int,
     *     'firstName': string,
     *     'lastName': null|string,
     *     'tags': null|list<string>,
     * }
     */
    #[Override]
    public function jsonSerialize() : array
    {
        return [
            'firstName' => (string) $this->firstName,
            'age' => $this->age,
            'lastName' => $this->lastName !== null ? (string) $this->lastName : null,
            'tags' => $this->tags,
        ];
    }
}
