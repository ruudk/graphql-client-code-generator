<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Query\Test\Data;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Query\Test\TestQuery;

/**
 * `@throwWhenNull` on a `@hook` field whose hook returns a nullable type makes the
 * property non-nullable. When the hook returns null, reading the property throws.
 */
final class HooksThrowWhenNullTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()
            ->enableThrowWhenNullDirective()
            ->withHook(FindUserByIdHook::class)
            ->withHook(FindAccountByIdHook::class);
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testHooksResolveToNonNullValues(): void
    {
        $customer = $this->execute()->customers[0];

        self::assertSame('Alice', $customer->user->name);
        self::assertSame('enterprise', $customer->account->plan);
    }

    public function testHookThrowsWhenNull(): void
    {
        $customer = $this->execute()->customers[1];

        $this->expectException(NodeNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Field Customer.user is null');

        self::fail(sprintf('Expected an exception, got "%s".', $customer->user->name));
    }

    public function testBatchedHookThrowsWhenNull(): void
    {
        $customer = $this->execute()->customers[1];

        $this->expectException(NodeNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Field Customer.account is null');

        self::fail(sprintf('Expected an exception, got "%s".', $customer->account->plan));
    }

    private function execute(): Data
    {
        return new TestQuery(
            $this->getClient([
                'data' => [
                    'customers' => [
                        [
                            'id' => 'customer-1',
                            'userId' => 'user-1',
                            'accountId' => 'account-1',
                        ],
                        [
                            'id' => 'customer-2',
                            'userId' => 'user-unknown',
                            'accountId' => 'account-unknown',
                        ],
                    ],
                ],
            ]),
            [
                'findUserById' => new FindUserByIdHook([
                    'user-1' => new User('user-1', 'Alice'),
                ]),
                'findAccountById' => new FindAccountByIdHook([
                    'account-1' => new Account('account-1', 'enterprise'),
                ]),
            ],
        )->execute();
    }
}
