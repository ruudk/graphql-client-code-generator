<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Hook;

// This file was automatically generated and should not be edited.

final class OrderDiscountId
{
    public string $discountId {
        get => $this->discountId ??= $this->data['discountId'];
    }

    /**
     * @param array{
     *     'discountId': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
