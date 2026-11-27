<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ScalarDecoder\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\FindOwnerHook;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeId;
use Ruudk\GraphQLCodeGenerator\ScalarDecoder\Fixture\NodeIdDecoder;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test($id: ID!) {
          node(id: $id) {
            __typename
            id
            ... on User {
              id
              name
            }
          }
          narrowed: node(id: $id) {
            __typename
            id
            ... on Project {
              name
            }
          }
          viewer {
            id
            name
            friendIds
            bestFriendId
            manager {
              id
            }
          }
          projects {
            ...ProjectFields
            ... on Project {
              ownerId
            }
          }
        }
        
        fragment ProjectFields on Project {
          id
          name
        }
        
        GRAPHQL;

    /**
     * @param array{
     *     'findOwner': FindOwnerHook,
     *     ...,
     * } $hooks
     * @param array{
     *     'ID': NodeIdDecoder,
     *     ...,
     * } $decoders
     */
    public function __construct(
        private TestClient $client,
        #[Autowire([
            'findOwner' => new Autowire(service: FindOwnerHook::class)
        ])]
        private array $hooks,
        #[Autowire([
            'ID' => new Autowire(service: 'app.node_id_decoder')
        ])]
        private array $decoders,
    ) {}

    /**
     * @api
     */
    public function execute(
        NodeId $id,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'id' => $id,
            ],
            self::OPERATION_NAME,
        );

        // @mago-expect analysis:mixed-argument(2)
        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [], // @phpstan-ignore argument.type
            $this->hooks,
            $this->decoders,
        );
    }
}
