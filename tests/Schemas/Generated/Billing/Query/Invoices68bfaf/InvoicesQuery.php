<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\Invoices68bfaf;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class InvoicesQuery {
    public const string OPERATION_NAME = 'Invoices';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Invoices {
          bills {
            ...InvoiceRow
          }
        }
        
        fragment InvoiceRow on Invoice {
          id
          total
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

        // @mago-expect analysis:mixed-argument(2)
        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
