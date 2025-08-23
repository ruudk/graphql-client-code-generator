<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Fragment\ProjectView;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Fragment\ProjectView\Creator\AsAdmin;
use Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Fragment\ProjectView\Creator\AsUser;

// This file was automatically generated and should not be edited.

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
        get {
            if (isset($this->asAdmin)) {
                return $this->asAdmin;
            }

            if ($this->data['__typename'] !== 'Admin') {
                return $this->asAdmin = null;
            }

            if (! array_key_exists('name', $this->data)) {
                return $this->asAdmin = null;
            }

            if (! array_key_exists('role', $this->data)) {
                return $this->asAdmin = null;
            }

            return $this->asAdmin = new AsAdmin($this->data);
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->asAdmin
     */
    public bool $isAdmin {
        get => $this->isAdmin ??= $this->data['__typename'] === 'Admin';
    }

    public ?AsUser $asUser {
        get {
            if (isset($this->asUser)) {
                return $this->asUser;
            }

            if ($this->data['__typename'] !== 'User') {
                return $this->asUser = null;
            }

            if (! array_key_exists('name', $this->data)) {
                return $this->asUser = null;
            }

            return $this->asUser = new AsUser($this->data);
        }
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
     *     'name'?: string,
     *     'role'?: string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
