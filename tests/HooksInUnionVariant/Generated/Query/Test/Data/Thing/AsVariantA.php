<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Query\Test\Data\Thing;

use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\User;

// This file was automatically generated and should not be edited.

final class AsVariantA
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['VariantA'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $realFieldA {
        get => $this->realFieldA ??= $this->data['realFieldA'];
    }

    public ?User $user {
        get => $this->user ??= $this->hooks['findUserById']->__invoke($this->id);
    }

    /**
     * @param array{
     *     '__typename': 'VariantA',
     *     'id': string,
     *     'realFieldA': string,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
