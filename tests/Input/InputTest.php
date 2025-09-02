<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Input;

use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Input\Fixture\Name;
use Ruudk\GraphQLCodeGenerator\Input\Generated\Input\CreateUserInput;
use Ruudk\GraphQLCodeGenerator\Input\Generated\Mutation\Test\TestMutation;

final class InputTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new TestMutation($this->getClient([
            'data' => [
                'sayHello' => 'Hello, Ruud!',
                'createUser' => true,
            ],
        ], new GraphQLRequestMatcher([
            'firstName' => 'Ruud',
            'input' => [
                'firstName' => 'Ruud',
                'age' => 99,
                'lastName' => 'Kamphuis',
            ],
            'lastName' => 'Kamphuis',
        ])))->execute(
            new Name('Ruud'),
            new CreateUserInput(
                new Name('Ruud'),
                99,
                new Name('Kamphuis'),
            ),
            new Name('Kamphuis'),
        );

        self::assertSame('Hello, Ruud!', $result->sayHello);
        self::assertTrue($result->createUser);
    }
}
