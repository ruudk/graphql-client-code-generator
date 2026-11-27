<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\Nullable\Data;

use Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class Node
{
    public string $login {
        get => $this->login ??= $this->data['login'];
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
            throw NodeNotFoundException::unexpectedType('Query', 'node', 'User', $data['__typename']);
        }

        $this->data = $data;
    }
}
