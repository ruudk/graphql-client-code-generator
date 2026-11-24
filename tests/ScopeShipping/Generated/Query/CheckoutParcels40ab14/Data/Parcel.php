<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScopeShipping\Generated\Query\CheckoutParcels40ab14\Data;

// This file was automatically generated and should not be edited.

final class Parcel
{
    public string $trackingCode {
        get => $this->trackingCode ??= $this->data['trackingCode'];
    }

    /**
     * @param array{
     *     'trackingCode': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
