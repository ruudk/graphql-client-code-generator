<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Query\PingAsActor;

use Ruudk\GraphQLCodeGenerator\OperationArgument\Actor;
use Ruudk\GraphQLCodeGenerator\OperationArgument\ActorTestClient;

// This file was automatically generated and should not be edited.

final readonly class PingAsActorQuery {
    public const string OPERATION_NAME = 'PingAsActor';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query PingAsActor {
          ping
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
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
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
     * @throws PingAsActorQueryFailedException
     */
    public function executeOrThrow(
        Actor $actor,
    ) : Data {
        $data = $this->execute(
            $actor,
        );

        if ($data->errors !== []) {
            throw new PingAsActorQueryFailedException($data);
        }

        return $data;
    }
}
