<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment\ProjectView;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment\ProjectView\Creator\AsAdmin;
use Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment\ProjectView\Creator\AsUser;

// This file was automatically generated and should not be edited.

/**
 * ... on Creator {
 *   __typename
 *   ... on User {
 *     name
 *   }
 *   ... on Admin {
 *     name
 *     role
 *   }
 * }
 */
final class Creator
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User', 'Admin'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsAdmin $asAdmin {
        get => $this->asAdmin ??= $this->data['__typename'] === 'Admin' ? new AsAdmin($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asAdmin
     */
    public bool $isAdmin {
        get => $this->isAdmin ??= $this->data['__typename'] === 'Admin';
    }

    public ?AsUser $asUser {
        get => $this->asUser ??= $this->data['__typename'] === 'User' ? new AsUser($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->asUser
     */
    public bool $isUser {
        get => $this->isUser ??= $this->data['__typename'] === 'User';
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
