<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          things {
            __typename
            id
            ... on VariantA {
              realFieldA
            }
            ... on VariantB {
              realFieldB
            }
          }
        }
        
        GRAPHQL;

    /**
     * @param array{
     *     'findUserById': FindUserByIdHook,
     * } $hooks
     */
    public function __construct(
        private TestClient $client,
        private array $hooks,
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
            $data['errors'] ?? [], // @phpstan-ignore argument.type
            $this->hooks,
        );
    }
}
