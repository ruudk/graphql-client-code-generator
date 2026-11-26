<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\CheckoutParcelsaa940f;

use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\CheckoutParcelsaa940f\Data\Parcel;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Parcel>
     */
    public array $parcels {
        get => $this->parcels ??= array_map(fn($item) => new Parcel($item), $this->data['parcels']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'parcels': list<array{
     *         'trackingCode': string,
     *         ...,
     *     }>,
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
