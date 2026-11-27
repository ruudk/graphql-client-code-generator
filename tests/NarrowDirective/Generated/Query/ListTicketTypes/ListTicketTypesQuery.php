<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\ListTicketTypes;

use Ruudk\GraphQLCodeGenerator\TestClient;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class ListTicketTypesQuery {
    public const string OPERATION_NAME = 'ListTicketTypes';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query ListTicketTypes($id: ID!, $first: Int!, $after: String) {
          event: node(id: $id) {
            __typename
            id
            ... on Event {
              name
              types(first: $first, after: $after) {
                edges {
                  cursor
                  node {
                    id
                    title
                  }
                }
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
        Stringable|string $id,
        int $first,
        null|Stringable|string $after = null,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'id' => (string) $id,
                'first' => $first,
                'after' => $after !== null ? (string) $after : null,
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
