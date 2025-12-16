<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListScalar;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\ListScalar\Generated\Query\Test\TestQuery;
use Ruudk\GraphQLCodeGenerator\ListScalar\ValueObjects\Currency;
use Symfony\Component\TypeInfo\Type;

/**
 * Test that lists of custom scalars are correctly typed in the generated code.
 *
 * This tests the fix for a bug where the $builtInOnly parameter was not correctly
 * passed when processing ListOfType in TypeMapper::mapGraphQLTypeToPHPType().
 *
 * The bug caused the $data parameter docblock to incorrectly use the PHP mapped type
 * (e.g., Currency) instead of the primitive JSON type (e.g., string) for list items.
 */
final class ListScalarTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withScalar('Currency', Type::string(), Type::object(Currency::class));
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQueryWithListOfScalars() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'supportedCurrencies' => ['EUR', 'USD', 'GBP'],
                'wallet' => [
                    'name' => 'My Wallet',
                    'currencies' => ['EUR', 'USD'],
                ],
            ],
        ]))->execute();

        // Verify the list of currencies is correctly converted to Currency objects
        self::assertCount(3, $result->supportedCurrencies);
        self::assertSame('EUR', $result->supportedCurrencies[0]->code);
        self::assertSame('USD', $result->supportedCurrencies[1]->code);
        self::assertSame('GBP', $result->supportedCurrencies[2]->code);

        // Verify nested list of currencies
        self::assertSame('My Wallet', $result->wallet->name);
        self::assertCount(2, $result->wallet->currencies);
        self::assertSame('EUR', $result->wallet->currencies[0]->code);
        self::assertSame('USD', $result->wallet->currencies[1]->code);
    }
}
