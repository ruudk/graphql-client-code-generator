<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScopeShipping\Generated\Query\CheckoutParcels40ab14;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class CheckoutParcelsQuery {
    public const string OPERATION_NAME = 'CheckoutParcels';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query CheckoutParcels {
          parcels {
            trackingCode
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    /**
     * @api
     */
    public function execute() : Data
    {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
