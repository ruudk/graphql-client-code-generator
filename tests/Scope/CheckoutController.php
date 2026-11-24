<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Scope;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Scope\Generated\Query\CheckoutViewer59d12c\CheckoutViewerQuery;
use Ruudk\GraphQLCodeGenerator\ScopeShipping\Generated\Query\CheckoutParcels40ab14\CheckoutParcelsQuery;

final readonly class CheckoutController
{
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
        #[GeneratedGraphQLClient(self::VIEWER, scope: 'billing')]
        public CheckoutViewerQuery $viewer,
        #[GeneratedGraphQLClient(self::PARCELS, scope: 'shipping')]
        public CheckoutParcelsQuery $parcels,
    ) {}
}
