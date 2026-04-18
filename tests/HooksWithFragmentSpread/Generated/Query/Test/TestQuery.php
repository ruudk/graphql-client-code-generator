<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test {
          viewer {
            login
            projects {
              ...ProjectListing
            }
          }
        }
        
        fragment ProjectListing on Project {
          ...ProjectSummary
          description
        }
        
        fragment ProjectSummary on Project {
          name
          creator {
            id
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
        #[Autowire([
            'findUserById' => new Autowire(service: FindUserByIdHook::class)
        ])]
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
