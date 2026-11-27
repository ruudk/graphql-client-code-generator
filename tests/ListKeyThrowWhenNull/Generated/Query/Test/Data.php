<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data\ByCode;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data\ByOrderItem;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data\ByOrderItemAndOrder;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data\ByStatus;
use Ruudk\GraphQLCodeGenerator\ListKeyThrowWhenNull\Generated\Query\Test\Data\ByStatusAndOrderItem;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var array<string, ByCode>
     */
    public array $byCode {
        get => $this->byCode ??= array_combine(
            array_map(fn($item) => (($item['orderItem'] ?? throw NodeNotFoundException::create('Payout', 'orderItem'))['code'] ?? throw NodeNotFoundException::create('OrderItem', 'code')), $this->data['byCode']),
            array_map(fn($item) => new ByCode($item), $this->data['byCode']),
        );
    }

    /**
     * @var array<string, ByOrderItem>
     */
    public array $byOrderItem {
        get => $this->byOrderItem ??= array_combine(
            array_map(fn($item) => ($item['orderItem'] ?? throw NodeNotFoundException::create('Payout', 'orderItem'))['id'], $this->data['byOrderItem']),
            array_map(fn($item) => new ByOrderItem($item), $this->data['byOrderItem']),
        );
    }

    /**
     * @var array<string, array<string, ByOrderItemAndOrder>>
     */
    public array $byOrderItemAndOrder {
        get => $this->byOrderItemAndOrder ??= (function() {
            $result = [];
            foreach ($this->data['byOrderItemAndOrder'] as $item) {
                $result[($item['orderItem'] ?? throw NodeNotFoundException::create('Payout', 'orderItem'))['id']][($item['orderItem']['order'] ?? throw NodeNotFoundException::create('OrderItem', 'order'))['id']] = new ByOrderItemAndOrder($item);
            }

            return $result;
        })();
    }

    /**
     * @var array<string, list<ByStatus>>
     */
    public array $byStatus {
        get => $this->byStatus ??= (function() {
            $result = [];
            foreach ($this->data['byStatus'] as $item) {
                $result[(($item['orderItem'] ?? throw NodeNotFoundException::create('Payout', 'orderItem'))['order'] ?? throw NodeNotFoundException::create('OrderItem', 'order'))['status']][] = new ByStatus($item);
            }

            return $result;
        })();
    }

    /**
     * @var array<string, array<string, list<ByStatusAndOrderItem>>>
     */
    public array $byStatusAndOrderItem {
        get => $this->byStatusAndOrderItem ??= (function() {
            $result = [];
            foreach ($this->data['byStatusAndOrderItem'] as $item) {
                $result[(($item['orderItem'] ?? throw NodeNotFoundException::create('Payout', 'orderItem'))['order'] ?? throw NodeNotFoundException::create('OrderItem', 'order'))['status']][$item['orderItem']['id']][] = new ByStatusAndOrderItem($item);
            }

            return $result;
        })();
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'byCode': list<array{
     *         'id': string,
     *         'orderItem': null|array{
     *             'code': null|string,
     *             ...,
     *         },
     *         ...,
     *     }>,
     *     'byOrderItem': list<array{
     *         'id': string,
     *         'orderItem': null|array{
     *             'id': string,
     *             ...,
     *         },
     *         ...,
     *     }>,
     *     'byOrderItemAndOrder': list<array{
     *         'id': string,
     *         'orderItem': null|array{
     *             'id': string,
     *             'order': null|array{
     *                 'id': string,
     *                 'status': string,
     *                 ...,
     *             },
     *             ...,
     *         },
     *         ...,
     *     }>,
     *     'byStatus': list<array{
     *         'id': string,
     *         'orderItem': null|array{
     *             'id': string,
     *             'order': null|array{
     *                 'id': string,
     *                 'status': string,
     *                 ...,
     *             },
     *             ...,
     *         },
     *         ...,
     *     }>,
     *     'byStatusAndOrderItem': list<array{
     *         'id': string,
     *         'orderItem': null|array{
     *             'id': string,
     *             'order': null|array{
     *                 'id': string,
     *                 'status': string,
     *                 ...,
     *             },
     *             ...,
     *         },
     *         ...,
     *     }>,
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
