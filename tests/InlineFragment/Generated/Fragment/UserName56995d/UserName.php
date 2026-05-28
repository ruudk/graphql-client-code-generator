<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Fragment\UserName56995d;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineFragment\UserMapper;

// This file was automatically generated and should not be edited.

#[Generated(
    source: UserMapper::class,
    restricted: true,
    restrictInstantiation: true,
)]
final class UserName
{
    public string $firstName {
        get => $this->firstName ??= $this->data['firstName'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $lastName {
        get => $this->lastName ??= $this->data['lastName'];
    }

    /**
     * @param array{
     *     'firstName': string,
     *     'id': string,
     *     'lastName': string,
     *     ...<int|string, mixed>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
