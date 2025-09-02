<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Input\Generated\Mutation\Test;

use Ruudk\GraphQLCodeGenerator\Input\Generated\Input\CreateUserInput;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.

final readonly class TestMutation {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        mutation Test($firstName: String!, $lastName: String, $input: CreateUserInput!) {
          sayHello(firstName: $firstName, lastName: $lastName)
          createUser(input: $input)
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    public function execute(
        string $firstName,
        CreateUserInput $input,
        ?string $lastName = null,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'firstName' => $firstName,
                'input' => $input,
                'lastName' => $lastName,
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
