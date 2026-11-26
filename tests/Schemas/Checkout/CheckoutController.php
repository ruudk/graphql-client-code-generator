<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Checkout;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\CheckoutViewer0d4a41\CheckoutViewerQuery;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Shipping\Query\CheckoutParcelsaa940f\CheckoutParcelsQuery;

/**
 * Both schemas read this directory, so every attribute names its schema.
 */
final readonly class CheckoutController
{
    private const string BILLING = 'billing';
    private const string VIEWER = <<<'GRAPHQL'
        query CheckoutViewer {
          viewer {
            login
          }
        }
        GRAPHQL;
    private const string PARCELS = <<<'GRAPHQL'
        query CheckoutParcels {
          parcels {
            trackingCode
          }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::VIEWER, self::BILLING)]
        public CheckoutViewerQuery $viewer,
        #[GeneratedGraphQLClient(self::PARCELS, schema: 'shipping')]
        public CheckoutParcelsQuery $parcels,
    ) {}
}
