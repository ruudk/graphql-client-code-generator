<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\TestQuery;

final class NoIndexByTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->enableSymfonyExcludeAttribute()
            ->enableIndexByDirective()
            ->enableAddNodesOnConnections();
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testItemsShouldNotBeIndexedWhenNoDirective() : void
    {
        $result = new TestQuery($this->getClient([
            'data' => [
                'transactions' => [
                    'edges' => [
                        [
                            'node' => [
                                'id' => 'transaction-1',
                                'workflow' => [
                                    'request' => [
                                        'id' => 'request-1',
                                        'items' => [
                                            [
                                                'id' => 'item-1',
                                            ],
                                            [
                                                'id' => 'item-2',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]))->execute();
        self::assertArrayHasKey('transaction-1', $result->transactions->edges);
        $transaction = $result->transactions->edges['transaction-1']->node;
        self::assertSame('transaction-1', $transaction->id);
        self::assertNotNull($transaction->workflow);
        $request = $transaction->workflow->request;
        self::assertNotNull($request);
        self::assertSame('request-1', $request->id);
        self::assertCount(2, $request->items);
        self::assertSame('item-1', $request->items[0]->id);
        self::assertSame('item-2', $request->items[1]->id);
    }
}
