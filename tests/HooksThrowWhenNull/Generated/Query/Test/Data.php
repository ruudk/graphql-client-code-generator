<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Account;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\FindAccountByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Hook\CustomerAccountId;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\HookLoader;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Query\Test\Data\Customer;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Customer>
     */
    public array $customers {
        get => $this->customers ??= array_map(fn($item) => new Customer($item, $this->hooks, $this->loaders), $this->data['customers']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @var array{
     *     findAccountById: HookLoader<CustomerAccountId, null|Account>,
     *     ...<string, HookLoader<mixed, mixed>>,
     * }
     */
    private readonly array $loaders;

    /**
     * @param array{
     *     'customers': list<array{
     *         'accountId': string,
     *         'id': string,
     *         'userId': string,
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
     * @param array{
     *     'findAccountById': FindAccountByIdHook,
     *     'findUserById': FindUserByIdHook,
     *     ...,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);

        $this->loaders = [
            'findAccountById' => new HookLoader(
                $this->collectHookFindAccountByIdInputs(...),
                $this->hooks['findAccountById']->__invoke(...),
            ),
        ];
    }

    /**
     * @return iterable<array{object, CustomerAccountId}>
     */
    private function collectHookFindAccountByIdInputs() : iterable
    {
        foreach ($this->customers as $item) {
            yield [$item, $item->buildCustomerAccountId()];
        }
    }
}
