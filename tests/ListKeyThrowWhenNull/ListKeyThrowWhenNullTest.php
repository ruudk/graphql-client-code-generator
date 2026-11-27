<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull;

use Closure;
use Override;
use Ruudk\GraphQLCodeGenerator\Config\SchemaConfig;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\TestQuery;

final class ListKeyThrowWhenNullTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig(): SchemaConfig
    {
        return parent::getConfig()->enableIndexByDirective()->enableGroupByDirective()->enableThrowWhenNullDirective();
    }

    public function testGenerate(): void
    {
        $this->assertActualMatchesExpected();
    }

    public function testQuery(): void
    {
        $result = new TestQuery($this->getClient($this->payload([
            $this->payout('1', $this->orderItem('oi1', 'A', $this->order('o1', 'PAID'))),
            $this->payout('2', $this->orderItem('oi2', 'B', $this->order('o2', 'OPEN'))),
            $this->payout('3', $this->orderItem('oi3', 'C', $this->order('o1', 'PAID'))),
        ])))->execute();

        self::assertSame(['oi1', 'oi2', 'oi3'], array_keys($result->byOrderItem));
        self::assertSame('2', $result->byOrderItem['oi2']->id);

        self::assertSame(['A', 'B', 'C'], array_keys($result->byCode));
        self::assertSame('3', $result->byCode['C']->id);

        self::assertSame(['oi1', 'oi2', 'oi3'], array_keys($result->byOrderItemAndOrder));
        self::assertSame(['o1'], array_keys($result->byOrderItemAndOrder['oi3']));
        self::assertSame('3', $result->byOrderItemAndOrder['oi3']['o1']->id);

        self::assertSame(['PAID', 'OPEN'], array_keys($result->byStatus));
        self::assertSame(['1', '3'], array_map(static fn($payout) => $payout->id, $result->byStatus['PAID']));

        self::assertSame(['PAID', 'OPEN'], array_keys($result->byStatusAndOrderItem));
        self::assertSame(['oi1', 'oi3'], array_keys($result->byStatusAndOrderItem['PAID']));
        self::assertSame('2', $result->byStatusAndOrderItem['OPEN']['oi2'][0]->id);
    }

    public function testIndexByThrowsWhenObjectInPathIsNull(): void
    {
        $result = new TestQuery($this->getClient($this->payload([
            $this->payout('1', $this->orderItem('oi1', 'A', $this->order('o1', 'PAID'))),
            $this->payout('2', null),
        ])))->execute();

        self::assertNodeNotFound('Field Payout.orderItem is null', static fn() => $result->byOrderItem);
    }

    public function testIndexByThrowsWhenKeyIsNull(): void
    {
        $result = new TestQuery($this->getClient($this->payload([
            $this->payout('1', $this->orderItem('oi1', null, $this->order('o1', 'PAID'))),
        ])))->execute();

        self::assertNodeNotFound('Field OrderItem.code is null', static fn() => $result->byCode);
    }

    public function testMultiFieldIndexByThrowsWhenNestedObjectIsNull(): void
    {
        $result = new TestQuery($this->getClient($this->payload([
            $this->payout('1', $this->orderItem('oi1', 'A', null)),
        ])))->execute();

        self::assertNodeNotFound('Field OrderItem.order is null', static fn() => $result->byOrderItemAndOrder);
    }

    public function testGroupByThrowsWhenObjectInPathIsNull(): void
    {
        $result = new TestQuery($this->getClient($this->payload([
            $this->payout('1', null),
        ])))->execute();

        self::assertNodeNotFound('Field Payout.orderItem is null', static fn() => $result->byStatus);
    }

    public function testMultiFieldGroupByThrowsWhenNestedObjectIsNull(): void
    {
        $result = new TestQuery($this->getClient($this->payload([
            $this->payout('1', $this->orderItem('oi1', 'A', null)),
        ])))->execute();

        self::assertNodeNotFound('Field OrderItem.order is null', static fn() => $result->byStatusAndOrderItem);
    }

    /**
     * @param Closure(): mixed $read
     */
    private static function assertNodeNotFound(string $message, Closure $read): void
    {
        try {
            $read();
        } catch (NodeNotFoundException $exception) {
            self::assertSame($message, $exception->getMessage());

            return;
        }

        self::fail(sprintf('Expected NodeNotFoundException "%s"', $message));
    }

    /**
     * @param list<array<string, mixed>> $payouts
     * @return array<string, mixed>
     */
    private function payload(array $payouts): array
    {
        return [
            'data' => [
                'byOrderItem' => $payouts,
                'byCode' => $payouts,
                'byOrderItemAndOrder' => $payouts,
                'byStatus' => $payouts,
                'byStatusAndOrderItem' => $payouts,
            ],
        ];
    }

    /**
     * @param null|array<string, mixed> $orderItem
     * @return array<string, mixed>
     */
    private function payout(string $id, ?array $orderItem): array
    {
        return [
            'id' => $id,
            'orderItem' => $orderItem,
        ];
    }

    /**
     * @param null|array<string, mixed> $order
     * @return array<string, mixed>
     */
    private function orderItem(string $id, ?string $code, ?array $order): array
    {
        return [
            'id' => $id,
            'code' => $code,
            'order' => $order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function order(string $id, string $status): array
    {
        return [
            'id' => $id,
            'status' => $status,
        ];
    }
}
