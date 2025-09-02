<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OneOfDirective;

use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Input\UserByInput;
use Ruudk\GraphQLCodeGenerator\OneOfDirective\Generated\Query\Test\TestQuery;

final class OneOfDirectiveTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQueryByEmail() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'user' => [
                    'id' => '123',
                    'email' => 'john@example.com',
                ],
            ],
        ], new GraphQLRequestMatcher([
            'by' => [
                'id' => null,
                'email' => 'john@example.com',
            ],
        ])))->execute(UserByInput::createEmail('john@example.com'));
        self::assertSame('123', $result->user?->id);
        self::assertSame('john@example.com', $result->user->email);
    }

    public function testQueryById() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'user' => [
                    'id' => '123',
                    'email' => 'john@example.com',
                ],
            ],
        ], new GraphQLRequestMatcher([
            'by' => [
                'id' => '123',
                'email' => null,
            ],
        ])))->execute(UserByInput::createId('123'));
        self::assertSame('123', $result->user?->id);
        self::assertSame('john@example.com', $result->user->email);
    }
}
