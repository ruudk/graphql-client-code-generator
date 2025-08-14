<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer\Data;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

/**
 * {
 *   __typename
 *   login
 * }
 */
#[Exclude]
final class Viewer
{
    public string $__typename {
        get => $this->data['__typename'];
    }

    public string $login {
        get => $this->data['login'];
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
