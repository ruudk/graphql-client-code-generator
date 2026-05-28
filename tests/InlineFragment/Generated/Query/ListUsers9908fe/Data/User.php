<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\ListUsers9908fe\Data;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Fragment\UserName56995d\UserName;
use Ruudk\GraphQLCodeGenerator\InlineFragment\ListUsersClient;

// This file was automatically generated and should not be edited.

#[Generated(
    source: ListUsersClient::class,
    restricted: true,
    restrictInstantiation: true,
)]
final class User
{
    public UserName $userName {
        get => $this->userName ??= new UserName($this->data);
    }

    /**
     * @param array{
     *     'firstName': string,
     *     'id': string,
     *     'lastName': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
