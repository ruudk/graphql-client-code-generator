<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Query\Projectsd4cba6;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Ruudk\GraphQLCodeGenerator\Twig\SomeController;

// This file was automatically generated and should not be edited.

#[Generated(
    source: SomeController::class,
    restricted: true,
)]
final readonly class ProjectsQuery {
    public const string OPERATION_NAME = 'Projects';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Projects {
          ...AdminProjectList
        }
        
        fragment AdminProjectList on Query {
          viewer {
            name
          }
          projects {
            ...AdminProjectRow
          }
        }
        
        fragment AdminProjectOptions on Project {
          state
        }
        
        fragment AdminProjectRow on Project {
          id
          name
          description
          ...AdminProjectOptions
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
     * @throws ProjectsQueryFailedException
     */
    public function executeOrThrow() : Data
    {
        $data = $this->execute(
        );

        if ($data->errors !== []) {
            throw new ProjectsQueryFailedException($data);
        }

        return $data;
    }
}
