<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer\Data;

// This file was automatically generated and should not be edited.

/**
 * ... on User {
 *   __typename
 *   login
 * }
 */
final class Viewer
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'login': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
