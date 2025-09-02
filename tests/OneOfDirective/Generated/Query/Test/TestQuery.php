<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Input\UserByInput;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test($by: UserByInput!) {
          user(by: $by) {
            id
            email
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    public function execute(
        UserByInput $by,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'by' => $by,
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
