<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Hook;

// This file was automatically generated and should not be edited.

final class CustomerAccountId
{
    public string $accountId {
        get => $this->accountId ??= $this->data['accountId'];
    }

    /**
     * @param array{
     *     'accountId': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
