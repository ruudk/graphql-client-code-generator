<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\ViewerProjects1d8480;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\SomeController;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

#[Generated(
    source: SomeController::class,
    restricted: true,
)]
final readonly class ViewerProjectsQuery {
    public const string OPERATION_NAME = 'ViewerProjects';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query ViewerProjects {
          viewer {
            login
            projects {
              name
              description
            }
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
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

    /**
     * @throws ViewerProjectsQueryFailedException
     */
    public function executeOrThrow() : Data
    {
        $data = $this->execute(
        );

        if ($data->errors !== []) {
            throw new ViewerProjectsQueryFailedException($data);
        }

        return $data;
    }
}
