<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Query;

use Ruudk\GraphQLCodeGenerator\Examples\Generated\Query\Viewer\Data;
use Ruudk\GraphQLCodeGenerator\Examples\GitHubClient;

// This file was automatically generated and should not be edited.
// Based on Viewer.graphql

final readonly class ViewerQuery {
    public const string OPERATION_NAME = 'Viewer';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Viewer {
          viewer {
            login
          }
        }
        
        GRAPHQL;

    public function __construct(
        private GitHubClient $client,
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
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
