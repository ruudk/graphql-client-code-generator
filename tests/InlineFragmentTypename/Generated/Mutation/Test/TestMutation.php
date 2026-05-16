<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestMutation {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        mutation Test {
          addCountry {
            __typename
            ... on Country {
              id
              name
            }
            ... on SupportedCountryError {
              __typename
            }
            ... on UnsupportedCountryError {
              __typename
              code
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
