<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Scope;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedGraphQLClient;
use Ruudk\GraphQLCodeGenerator\Scope\Generated\Query\BillingViewerece69a\BillingViewerQuery;

final readonly class BillingController
{
    private const string OPERATION = <<<'GRAPHQL'
        query BillingViewer {
          viewer {
            login
          }
        }
        GRAPHQL;

    public function __construct(
        #[GeneratedGraphQLClient(self::OPERATION, scope: 'billing')]
        public BillingViewerQuery $query,
    ) {}

    public function getLogin() : string
    {
        return $this->query->execute()->viewer->login;
    }
}
