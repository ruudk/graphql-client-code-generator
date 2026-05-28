<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Optimization\Generated\Fragment\AppUrl;
use Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test\Data\Viewer\AsUser;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public ?AppUrl $appUrl {
        get => $this->appUrl ??= $this->data['__typename'] === 'Application' ? new AppUrl($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->appUrl
     */
    public bool $isAppUrl {
        get => $this->isAppUrl ??= $this->data['__typename'] === 'Application';
    }

    public ?AsUser $asUser {
        get => $this->asUser ??= $this->data['__typename'] === 'User' ? new AsUser($this->data) : null;
    }

    /**
     * @api
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
     *     '__typename': 'Application',
     *     'id': string,
     *     'idAlias': string,
     *     'name': string,
     *     'url': string,
     * }|array{
     *     '__typename': 'User',
     *     'id': string,
     *     'idAlias': string,
     *     'login': string,
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
