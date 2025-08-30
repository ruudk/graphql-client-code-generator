<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Money;

use Generator;
use Override;
use Ruudk\CodeGenerator\CodeGenerator;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\Money\Generated\Query\ConvertMoneyQuery;
use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Currency;
use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Money;
use Ruudk\GraphQLCodeGenerator\Type\PseudoType;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\DelegatingTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\ObjectTypeInitializer;
use Ruudk\GraphQLCodeGenerator\TypeInitializer\TypeInitializer;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

final class MoneyTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withScalar('Currency', Type::string(), Type::object(Currency::class))
            ->withInputObjectType('MoneyInput', Type::object(Money::class))
            ->withObjectType(
                'Money',
                Type::arrayShape([
                    'amount' => [
                        'type' => new PseudoType('numeric-string'),
                        'optional' => false,
                    ],
                    'currency' => [
                        'type' => Type::string(),
                        'optional' => false,
                    ],
                ]),
                Type::object(Money::class),
            )
            ->withIgnoreType('MoneyInput')
            ->withIgnoreType('Money')
            ->withTypeInitializer(
                new ObjectTypeInitializer(
                    new class implements TypeInitializer {
                        #[Override]
                        public function supports(Type $type) : bool
                        {
                            return $type instanceof ObjectType && $type->getClassName() === Money::class;
                        }

                        /**
                         * @return Generator<\Ruudk\CodeGenerator\Group|string>
                         */
                        #[Override]
                        public function initialize(Type $type, CodeGenerator $generator, string $variable, DelegatingTypeInitializer $delegator) : Generator
                        {
                            yield sprintf('new %s(', $generator->import(Money::class));
                            yield $generator->indent(function () use ($generator, $variable) {
                                yield sprintf("%s['amount'],", $variable);
                                yield sprintf("new %s(%s['currency']),", $generator->import(Currency::class), $variable);
                            });
                            yield ')';
                        }
                    },
                ),
            );
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testConvertMoneyWithMoneyObject() : void
    {
        $inputMoney = Money::EUR('10000');
        $result = new ConvertMoneyQuery($this->getClient([
            'data' => [
                'convertMoney' => [
                    'amount' => '11000',
                    'currency' => 'USD',
                ],
                'total' => [
                    'amount' => '100',
                    'currency' => 'EUR',
                ],
                'subtotal' => [
                    'amount' => '100',
                    'currency' => 'EUR',
                ],
            ],
        ], new GraphQLRequestMatcher(
            variables: [
                'amount' => [
                    'amount' => '10000',
                    'currency' => 'EUR',
                ],
                'targetCurrency' => 'USD',
            ],
            operationName: 'ConvertMoney',
        )))->execute($inputMoney, new Currency('USD'));
        // Verify the result is a Money object (type is guaranteed by TypeMapper)
        self::assertSame('11000', $result->convertMoney->amount);
        self::assertSame('USD', $result->convertMoney->currency->code);
        // Verify Money object functionality
        self::assertFalse($inputMoney->equals($result->convertMoney)); // Different currencies
        self::assertEquals(Money::EUR('100'), $result->converter->total);
        self::assertEquals(Money::EUR('100'), $result->converter->subtotal);
    }

    public function testConvertMoneyWithDifferentCurrencies() : void
    {
        $inputMoney = Money::GBP('50000'); // 500.00 GBP
        $result = new ConvertMoneyQuery($this->getClient([
            'data' => [
                'convertMoney' => [
                    'amount' => '60000', // 600.00 EUR
                    'currency' => 'EUR',
                ],
            ],
        ], new GraphQLRequestMatcher(
            variables: [
                'amount' => [
                    'amount' => '50000',
                    'currency' => 'GBP',
                ],
                'targetCurrency' => 'EUR',
            ],
            operationName: 'ConvertMoney',
        )))->execute($inputMoney, Currency::EUR());
        self::assertSame('60000', $result->convertMoney->amount);
        self::assertSame('EUR', $result->convertMoney->currency->code);
    }

    public function testMoneyJsonSerialization() : void
    {
        $money = Money::USD('25000'); // 250.00 USD
        $json = $money->jsonSerialize();
        self::assertSame([
            'amount' => '25000',
            'currency' => 'USD',
        ], $json);
    }
}
