<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment\ProjectView\Creator;

// This file was automatically generated and should not be edited.

/**
 * ... on Admin {
 *   name
 *   role
 * }
 */
final class AsAdmin
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Admin'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsUser $asUser {
        get => $this->asUser ??= new AsUser($this->data);
    }

    /**
     * @phpstan-assert-if-true !null $this->asUser
     */
    public bool $isUser {
        get => $this->isUser ??= $this->data['__typename'] === 'User';
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public string $role {
        get => $this->role ??= $this->data['role'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'name': string,
     *     'role': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
