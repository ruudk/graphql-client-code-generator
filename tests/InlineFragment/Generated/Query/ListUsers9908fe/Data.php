<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\ListUsers9908fe;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineFragment\Generated\Query\ListUsers9908fe\Data\User;
use Ruudk\GraphQLCodeGenerator\InlineFragment\ListUsersClient;

// This file was automatically generated and should not be edited.

#[Generated(
    source: ListUsersClient::class,
    restricted: true,
    restrictInstantiation: true,
)]
final class Data
{
    /**
     * @var list<User>
     */
    public array $users {
        get => $this->users ??= array_map(fn($item) => new User($item), $this->data['users']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'users': list<array{
     *         'firstName': string,
     *         'id': string,
     *         'lastName': string,
     *     }>,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
