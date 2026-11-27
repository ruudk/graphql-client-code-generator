<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture\Handle;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Fixture\UserId;
use Ruudk\GraphQLCodeGenerator\ScalarInputType\Generated\Input\UserFilter;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test($id: ID!, $handle: Handle!, $ids: [ID!]!, $after: ID, $filter: UserFilter!, $name: String) {
          user(id: $id) {
            id
            name
          }
          userByHandle(handle: $handle) {
            id
          }
          users(ids: $ids, after: $after) {
            id
          }
          search(filter: $filter, name: $name) {
            id
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    /**
     * @api
     * @param list<UserId> $ids
     */
    public function execute(
        UserId $id,
        Handle|string $handle,
        array $ids,
        UserFilter $filter,
        ?UserId $after = null,
        null|Stringable|string $name = null,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'id' => $id,
                'handle' => $handle,
                'ids' => $ids,
                'filter' => $filter,
                'after' => $after,
                'name' => $name !== null ? (string) $name : null,
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
