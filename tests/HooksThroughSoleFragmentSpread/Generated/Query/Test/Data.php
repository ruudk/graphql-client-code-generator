<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\FindDiscountCodeByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Query\Test\Data\PaymentFlow;

// This file was automatically generated and should not be edited.

final class Data
{
    public PaymentFlow $paymentFlow {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->paymentFlow ??= $this->data['paymentFlow'] !== null ? new PaymentFlow($this->data['paymentFlow'], $this->hooks) : throw NodeNotFoundException::create('Query', 'paymentFlow');
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'paymentFlow': null|array{
     *         'id': string,
     *         'order': array{
     *             'discountId': string,
     *             'id': string,
     *             ...,
     *         },
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     * @param array{
     *     'findDiscountCodeById': FindDiscountCodeByIdHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
