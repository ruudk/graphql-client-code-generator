<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Account;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Hook\CustomerAccountId;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\Hook\CustomerUserId;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\HookLoader;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\HooksThrowWhenNull\User;

// This file was automatically generated and should not be edited.

final class Customer
{
    public Account $account {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->account ??= $this->loaders['findAccountById']->resolve($this) ?? throw NodeNotFoundException::create('Customer', 'account');
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public User $user {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->user ??= $this->hooks['findUserById']->__invoke($this->buildCustomerUserId()) ?? throw NodeNotFoundException::create('Customer', 'user');
    }

    /**
     * @param array{
     *     'accountId': string,
     *     'id': string,
     *     'userId': string,
     *     ...,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     *     ...,
     * } $hooks
     * @param array{
     *     findAccountById: HookLoader<CustomerAccountId, null|Account>,
     *     ...<string, HookLoader<mixed, mixed>>,
     * } $loaders
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
        private readonly array $loaders,
    ) {}

    /**
     * @internal
     */
    public function buildCustomerAccountId() : CustomerAccountId
    {
        return new CustomerAccountId($this->data);
    }

    /**
     * @internal
     */
    public function buildCustomerUserId() : CustomerUserId
    {
        return new CustomerUserId($this->data);
    }
}
