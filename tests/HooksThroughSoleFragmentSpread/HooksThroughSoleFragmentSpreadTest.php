<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Query\Test\TestQuery;

final class HooksThroughSoleFragmentSpreadTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableThrowWhenNullDirective()
            ->withHook(FindDiscountCodeByIdHook::class);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $findDiscountCodeById = new FindDiscountCodeByIdHook([
            'discount-1' => new DiscountCode('discount-1'),
        ]);

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'paymentFlow' => [
                        'id' => 'flow-1',
                        'order' => [
                            'id' => 'order-1',
                            'discountId' => 'discount-1',
                        ],
                    ],
                ],
            ]),
            [
                'findDiscountCodeById' => $findDiscountCodeById,
            ],
        )->execute('flow-1');

        self::assertSame('flow-1', $result->paymentFlow->showPaymentFlow->id);
        self::assertSame('order-1', $result->paymentFlow->showPaymentFlow->order->id);
        self::assertNotNull($result->paymentFlow->showPaymentFlow->order->discountCode);
        self::assertSame('discount-1', $result->paymentFlow->showPaymentFlow->order->discountCode->id);
    }
}
