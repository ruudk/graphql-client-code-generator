<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestMultiField;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestMultiFieldQuery {
    public const string OPERATION_NAME = 'TestMultiField';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query TestMultiField {
          projects {
            id
            name
          }
          issues {
            id
            name
          }
          customers {
            edges {
              node {
                id
                name
              }
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
