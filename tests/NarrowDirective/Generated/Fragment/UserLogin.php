<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Fragment;

// This file was automatically generated and should not be edited.

final class UserLogin
{
    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    /**
     * @param array{
     *     'login': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
