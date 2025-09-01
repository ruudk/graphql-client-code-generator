<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedFrom;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\Inline1d8480\Data;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\SomeController;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

#[GeneratedFrom(
    source: SomeController::class,
    restrict: true,
)]
final readonly class Inline1d8480Query {
    public const string OPERATION_NAME = 'Inline1d8480';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Inline1d8480 {
          viewer {
            login
            projects {
              name
              description
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
