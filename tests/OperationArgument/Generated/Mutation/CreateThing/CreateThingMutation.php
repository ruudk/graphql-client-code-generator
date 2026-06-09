<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Mutation\CreateThing;

use Ruudk\GraphQLCodeGenerator\OperationArgument\Actor;
use Ruudk\GraphQLCodeGenerator\OperationArgument\ActorTestClient;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class CreateThingMutation {
    public const string OPERATION_NAME = 'CreateThing';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        mutation CreateThing($name: String!) {
          createThing(name: $name) {
            id
            name
          }
        }
        
        GRAPHQL;

    public function __construct(
        private ActorTestClient $client,
    ) {}

    /**
     * @api
     */
    public function execute(
        Actor $actor,
        Stringable|string $name,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'name' => (string) $name,
            ],
            self::OPERATION_NAME,
            actor: $actor,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }

    /**
     * @api
     * @throws CreateThingMutationFailedException
     */
    public function executeOrThrow(
        Actor $actor,
        Stringable|string $name,
    ) : Data {
        $data = $this->execute(
            $actor,
            $name,
        );

        if ($data->errors !== []) {
            throw new CreateThingMutationFailedException($data);
        }

        return $data;
    }
}
