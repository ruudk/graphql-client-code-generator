<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class AsUser
{
    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    /**
     * @param array{
     *     '__typename': 'User',
     *     'login': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
