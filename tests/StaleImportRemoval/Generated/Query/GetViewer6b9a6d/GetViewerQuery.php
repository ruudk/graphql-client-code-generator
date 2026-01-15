<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewer6b9a6d;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class GetViewerQuery {
    public const string OPERATION_NAME = 'GetViewer';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query GetViewer {
          viewer {
            login
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
