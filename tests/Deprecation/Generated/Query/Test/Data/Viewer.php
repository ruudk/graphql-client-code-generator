<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Deprecation\Generated\Enum\Role;
use Ruudk\GraphQLCodeGenerator\Deprecation\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

final class Viewer
{
    /**
     * @deprecated Read the fields on `User`.
     */
    public string $bio {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->bio ??= $this->data['profile']['bio'];
    }

    /**
     * @deprecated Use `name`. Nicknames are no longer shown, see *\/nicknames for details.
     */
    public string $knownNickname {
        /**
         * @throws NodeNotFoundException
         * @mago-expect analysis:deprecated-property
         */
        get => $this->knownNickname ??= $this->data['knownNickname'] !== null ? $this->data['knownNickname'] : throw NodeNotFoundException::create('User', 'knownNickname');
    }

    /**
     * @deprecated No longer supported
     */
    public string $login {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->login ??= $this->data['login'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @deprecated Use `name`. Nicknames are no longer shown, see *\/nicknames for details.
     */
    public ?string $nickname {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->nickname ??= $this->data['nickname'] !== null ? $this->data['nickname'] : null;
    }

    /**
     * @throws NodeNotFoundException
     * @deprecated Use `name`. Nicknames are no longer shown, see *\/nicknames for details.
     */
    public string $nicknameOrThrow {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->nickname ?? throw NodeNotFoundException::create('User', 'nickname');
    }

    public Role $role {
        get => $this->role ??= Role::from($this->data['role']);
    }

    /**
     * @deprecated No longer supported
     */
    public string $username {
        /**
         * @mago-expect analysis:deprecated-property
         */
        get => $this->username ??= $this->data['username'];
    }

    /**
     * @param array{
     *     'knownNickname': null|string,
     *     'login': string,
     *     'name': string,
     *     'nickname': null|string,
     *     'profile': array{
     *         'bio': string,
     *         ...,
     *     },
     *     'role': string,
     *     'username': string,
     *     ...,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
