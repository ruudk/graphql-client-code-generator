<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\GroupByDirective\Generated\Query\TestMultiField;

use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestMultiFieldQuery {
    public const string OPERATION_NAME = 'TestMultiField';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query TestMultiField {
          issuesByAssigneeAndState: issues {
            id
            title
            state
            assignee {
              id
            }
          }
          customers {
            edges {
              node {
                id
                name
                tier
                country
              }
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
