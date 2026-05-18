<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\TestClient;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test($code: String!, $id: ID!) {
          supportedCountry(code: $code) {
            info {
              name
            }
            error {
              __typename
            }
          }
          order(id: $id) {
            __typename
            ... on MarketPlaceOrderItem {
              id
              fxFee {
                __typename
              }
            }
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    /**
     * @api
     */
    public function execute(
        Stringable|string $code,
        Stringable|string $id,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'code' => (string) $code,
                'id' => (string) $id,
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
