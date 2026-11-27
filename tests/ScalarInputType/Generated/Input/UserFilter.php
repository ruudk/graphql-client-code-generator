<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Input;

use JsonSerializable;
use Override;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture\Handle;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture\UserId;
use Stringable;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
final readonly class UserFilter implements JsonSerializable
{
    /**
     * @param null|list<UserId> $memberIds
     */
    public function __construct(
        public UserId $ownerId,
        public ?UserId $managerId = null,
        public ?array $memberIds = null,
        public null|Stringable|string $name = null,
        public null|Handle|string $handle = null,
    ) {}

    /**
     * @return array{
     *     'handle': null|Handle|string,
     *     'managerId': null|UserId,
     *     'memberIds': null|list<UserId>,
     *     'name': null|string,
     *     'ownerId': UserId,
     * }
     */
    #[Override]
    public function jsonSerialize() : array
    {
        return [
            'ownerId' => $this->ownerId,
            'managerId' => $this->managerId,
            'memberIds' => $this->memberIds,
            'name' => $this->name !== null ? (string) $this->name : null,
            'handle' => $this->handle,
        ];
    }
}
