<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Billing;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\Invoices68bfaf\InvoicesQuery;

/**
 * Only the billing schema reads this directory, so the attribute does not need to name it.
 */
final readonly class InvoiceController
{
    private const string OPERATION = <<<'GRAPHQL'
        query Invoices {
          bills {
            id
            total
          }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION)]
        public InvoicesQuery $invoices,
    ) {}
}
