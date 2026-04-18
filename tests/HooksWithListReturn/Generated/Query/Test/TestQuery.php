<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\FindUsersByIdsHook;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          viewer {
            login
            projects {
              name
              contributorIds
            }
          }
        }
        
        GRAPHQL;

    /**
     * @param array{
     *     'findUsersByIds': FindUsersByIdsHook,
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
