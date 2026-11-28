<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\JsonSerializable\Generated\Query\Narrow;

use Ruudk\GraphQLCodeGenerator\TestClient;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class NarrowQuery {
    public const string OPERATION_NAME = 'Narrow';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Narrow($id: ID!) {
          event: node(id: $id) {
            __typename
            ... on Event {
              id
              name
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
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'id' => (string) $id,
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
