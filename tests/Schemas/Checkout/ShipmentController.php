<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Checkout;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\ShipmentParcelsa15f3a\ShipmentParcelsQuery;

/**
 * Both schemas read this directory, but only the shipping schema has a client in it.
 */
final readonly class ShipmentController
{
    private const string OPERATION = <<<'GRAPHQL'
        query ShipmentParcels {
          parcels {
            trackingCode
          }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION, schema: 'shipping')]
        public ShipmentParcelsQuery $parcels,
    ) {}
}
