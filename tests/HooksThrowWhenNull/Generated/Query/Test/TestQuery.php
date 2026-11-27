<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\FindAccountByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          customers {
            id
            ... on Customer {
              userId
            }
            ... on Customer {
              accountId
            }
          }
        }
        
        GRAPHQL;

    /**
     * @param array{
     *     'findAccountById': FindAccountByIdHook,
     *     'findUserById': FindUserByIdHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private TestClient $client,
        private array $hooks,
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

        // @mago-expect analysis:mixed-argument(2)
        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [], // @phpstan-ignore argument.type
            $this->hooks,
        );
    }
}
