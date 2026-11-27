<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexByDirective\Generated\Query\TestFragments;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestFragmentsQuery {
    public const string OPERATION_NAME = 'TestFragments';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query TestFragments {
          projects {
            ...ProjectFields
          }
          issues {
            id
            name
          }
        }
        
        fragment ProjectFields on Project {
          id
          name
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
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
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
