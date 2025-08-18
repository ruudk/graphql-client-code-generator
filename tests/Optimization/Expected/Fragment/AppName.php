<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Expected\Fragment;

// This file was automatically generated and should not be edited.

/**
 * fragment AppName on Application {
 *   name
 * }
 */
final class AppName
{
    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
