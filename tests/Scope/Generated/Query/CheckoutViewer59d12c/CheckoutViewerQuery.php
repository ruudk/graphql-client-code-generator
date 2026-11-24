<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Scope\Generated\Query\CheckoutViewer59d12c;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class CheckoutViewerQuery {
    public const string OPERATION_NAME = 'CheckoutViewer';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query CheckoutViewer {
          viewer {
            login
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
