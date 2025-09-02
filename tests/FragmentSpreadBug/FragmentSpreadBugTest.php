<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails\GetTransactionDetailsQuery;
use Ruudk\GraphQLCodeGenerator\GraphQLRequestMatcher;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;

final class FragmentSpreadBugTest extends GraphQLTestCase
{
    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery() : void
    {
        $result = new GetTransactionDetailsQuery($this->getClient([
            'data' => [
                'transaction' => [
                    'id' => '1',
                    'state' => 'completed',
                    'transfers' => [
                        [
                            'id' => 'transfer1',
                            'state' => 'paid',
                            'createdAt' => '2024-01-01T00:00:00Z',
                            'total' => [
                                'amount' => '1000',
                                'currency' => 'USD',
                            ],
                            'transferReversals' => [
                                [
                                    'id' => 'reversal1',
                                    'state' => 'completed',
                                    'createdAt' => '2024-01-02T00:00:00Z',
                                    'total' => [
                                        'amount' => '500',
                                        'currency' => 'USD',
                                    ],
                                    'returnedAt' => '2024-01-03T00:00:00Z',
                                    'returnMethod' => 'bank_transfer',
                                    'resolutions' => [
                                        [
                                            'id' => 'resolution1',
                                            'createdAt' => '2024-01-04T00:00:00Z',
                                            'total' => [
                                                'amount' => '500',
                                                'currency' => 'USD',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], new GraphQLRequestMatcher(
            operationName: 'GetTransactionDetails',
        )))->execute();
        self::assertNotNull($result->transaction);
        self::assertSame('1', $result->transaction->id);
        self::assertSame('completed', $result->transaction->state);
        self::assertCount(1, $result->transaction->transfers);
        $transfer = $result->transaction->transfers[0];
        self::assertSame('transfer1', $transfer->id);
        self::assertSame('paid', $transfer->state);
        self::assertCount(1, $transfer->transferReversals);
        self::assertObjectNotHasProperty('createdAt', $transfer, 'Transfer should NOT have createdAt property');
        self::assertObjectNotHasProperty('total', $transfer, 'Transfer should NOT have total property');
        self::assertSame('transfer1', $transfer->transferDetails->id);
        self::assertSame('2024-01-01T00:00:00Z', $transfer->transferDetails->createdAt);
        self::assertSame('1000', $transfer->transferDetails->total->amount);
        self::assertSame('USD', $transfer->transferDetails->total->currency);
        self::assertCount(1, $transfer->transferDetails->transferReversals);
        $reversal = $transfer->transferReversals[0];
        self::assertSame('reversal1', $reversal->id);
        self::assertSame('completed', $reversal->state);
        $fragmentReversal = $transfer->transferDetails->transferReversals[0];
        self::assertSame('reversal1', $fragmentReversal->transferReversalDetails->id);
        self::assertSame('completed', $fragmentReversal->transferReversalDetails->state);
        self::assertSame('2024-01-02T00:00:00Z', $fragmentReversal->transferReversalDetails->createdAt);
        self::assertSame('500', $fragmentReversal->transferReversalDetails->total->amount);
        self::assertSame('USD', $fragmentReversal->transferReversalDetails->total->currency);
        self::assertSame('2024-01-03T00:00:00Z', $fragmentReversal->transferReversalDetails->returnedAt);
        self::assertSame('bank_transfer', $fragmentReversal->transferReversalDetails->returnMethod);
        self::assertNotNull($fragmentReversal->transferReversalDetails->resolutions);
        self::assertCount(1, $fragmentReversal->transferReversalDetails->resolutions);
    }
}
