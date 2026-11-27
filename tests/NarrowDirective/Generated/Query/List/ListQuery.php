<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NarrowDirective\Generated\Query\List;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class ListQuery {
    public const string OPERATION_NAME = 'List';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query List($ids: [ID!]!) {
          nodes(ids: $ids) {
            __typename
            ... on Event {
              name
              description
            }
          }
          search {
            __typename
            ... on User {
              ...UserLogin
            }
          }
        }
        
        fragment UserLogin on User {
          login
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    /**
     * @api
     * @param list<string> $ids
     */
    public function execute(
        array $ids,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'ids' => $ids,
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
