<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Hook;

// This file was automatically generated and should not be edited.

final class CustomerUserId
{
    public string $userId {
        get => $this->userId ??= $this->data['userId'];
    }

    /**
     * @param array{
     *     'userId': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
