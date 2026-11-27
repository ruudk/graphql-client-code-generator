<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\List\Data;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Fragment\UserLogin;
use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class Search
{
    public UserLogin $userLogin {
        get => $this->userLogin ??= new UserLogin($this->data);
    }

    /**
     * @var array{
     *     '__typename': string,
     *     'login': string,
     *     ...,
     * }
     */
    private readonly array $data;

    /**
     * @param array{
     *     '__typename': string,
     *     'login'?: string,
     *     ...,
     * } $data
     * @throws NodeNotFoundException
     */
    public function __construct(
        array $data,
    ) {
        if ($data['__typename'] !== 'User' || ! array_key_exists('login', $data)) {
            throw NodeNotFoundException::unexpectedType('Query', 'search', 'User', $data['__typename']);
        }

        $this->data = $data;
    }
}
