<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FlattenDirective\Generated\Fragment;

// This file was automatically generated and should not be edited.

final class UserName
{
    public string $firstName {
        get => $this->firstName ??= $this->data['firstName'];
    }

    public string $lastName {
        get => $this->lastName ??= $this->data['lastName'];
    }

    /**
     * @param array{
     *     'firstName': string,
     *     'lastName': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
