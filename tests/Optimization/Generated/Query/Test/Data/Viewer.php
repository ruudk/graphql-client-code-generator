<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Optimization\Generated\Fragment\AppUrl;
use Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test\Data\Viewer\AsUser;

// This file was automatically generated and should not be edited.

final class Viewer
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User', 'Application'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AppUrl $appUrl {
        get {
            if (isset($this->appUrl)) {
                return $this->appUrl;
            }

            if ($this->data['__typename'] !== 'Application') {
                return $this->appUrl = null;
            }

            if (! array_key_exists('url', $this->data)) {
                return $this->appUrl = null;
            }

            if (! array_key_exists('name', $this->data)) {
                return $this->appUrl = null;
            }

            return $this->appUrl = new AppUrl($this->data);
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->appUrl
     */
    public bool $isAppUrl {
        get => $this->isAppUrl ??= $this->data['__typename'] === 'Application';
    }

    public ?AsUser $asUser {
        get {
            if (isset($this->asUser)) {
                return $this->asUser;
            }

            if ($this->data['__typename'] !== 'User') {
                return $this->asUser = null;
            }

            if (! array_key_exists('login', $this->data)) {
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

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $idAlias {
        get => $this->idAlias ??= $this->data['idAlias'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'id': string,
     *     'idAlias': string,
     *     'login'?: string,
     *     'name': string,
     *     'url'?: string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
