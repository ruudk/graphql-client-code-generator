<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class GetTransactionDetailsQuery {
    public const string OPERATION_NAME = 'GetTransactionDetails';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query GetTransactionDetails {
          transaction {
            id
            state
            transfers {
              id
              state
              transferReversals {
                id
                state
              }
              ...TransferDetails
            }
          }
        }
        
        fragment TransferDetails on Transfer {
          id
          createdAt
          total {
            amount
            currency
          }
          transferReversals {
            ...TransferReversalDetails
          }
        }
        
        fragment TransferReversalDetails on TransferReversal {
          id
          createdAt
          state
          total {
            amount
            currency
          }
          returnedAt
          returnMethod
          resolutions {
            id
            createdAt
            total {
              amount
              currency
            }
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

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
