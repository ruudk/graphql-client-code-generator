<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Query\Ping;

use Ruudk\GraphQLCodeGenerator\OperationArgument\ActorTestClient;

// This file was automatically generated and should not be edited.

final readonly class PingQuery {
    public const string OPERATION_NAME = 'Ping';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Ping {
          ping
        }
        
        GRAPHQL;

    public function __construct(
        private ActorTestClient $client,
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

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }

    /**
     * @api
     * @throws PingQueryFailedException
     */
    public function executeOrThrow() : Data
    {
        $data = $this->execute(
        );

        if ($data->errors !== []) {
            throw new PingQueryFailedException($data);
        }

        return $data;
    }
}
