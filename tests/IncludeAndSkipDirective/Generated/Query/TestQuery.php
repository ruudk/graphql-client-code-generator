<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query;

use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\IncludeAndSkipDirective\Generated\Query\Test\TestQueryFailedException;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test($includeAdmin: Boolean!, $skipAdmin: Boolean!) {
          viewer {
            name
          }
          user2: user {
            name
          }
          admin @include(if: $includeAdmin) {
            name
          }
          admin2: admin @skip(if: $skipAdmin) {
            name
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    public function execute(
        bool $includeAdmin,
        bool $skipAdmin,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'includeAdmin' => $includeAdmin,
                'skipAdmin' => $skipAdmin,
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }

    /**
     * @throws TestQueryFailedException
     */
    public function executeOrThrow(
        bool $includeAdmin,
        bool $skipAdmin,
    ) : Data {
        $data = $this->execute(
            $includeAdmin,
            $skipAdmin,
        );

        if ($data->errors !== []) {
            throw new TestQueryFailedException($data);
        }

        return $data;
    }
}
