<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Query\Test\Data\Search;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Query\Test\Data\User;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Query\Test\Data\UserByHandle;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Search>
     */
    public array $search {
        get => $this->search ??= array_map(fn($item) => new Search($item), $this->data['search']);
    }

    public ?User $user {
        get => $this->user ??= $this->data['user'] !== null ? new User($this->data['user']) : null;
    }

    public ?UserByHandle $userByHandle {
        get => $this->userByHandle ??= $this->data['userByHandle'] !== null ? new UserByHandle($this->data['userByHandle']) : null;
    }

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
     *     'search': list<array{
     *         'id': string,
     *         ...,
     *     }>,
     *     'user': null|array{
     *         'id': string,
     *         'name': string,
     *         ...,
     *     },
     *     'userByHandle': null|array{
     *         'id': string,
     *         ...,
     *     },
     *     'users': list<array{
     *         'id': string,
     *         ...,
     *     }>,
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
