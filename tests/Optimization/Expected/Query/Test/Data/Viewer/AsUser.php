<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Expected\Query\Test\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\Optimization\Expected\Fragment\AppUrl;

// This file was automatically generated and should not be edited.

/**
 * ... on User {
 *   login
 *   name
 * }
 */
final class AsUser
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['User'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AppUrl $appUrl {
        get => $this->appUrl ??= new AppUrl($this->data);
    }

    /**
     * @phpstan-assert-if-true !null $this->appUrl
     */
    public bool $isAppUrl {
        get => $this->isAppUrl ??= $this->data['__typename'] === 'Application';
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $idAlias {
        get => $this->idAlias ??= $this->data['idAlias'];
    }

    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'id': string,
     *     'idAlias': string,
     *     'login': string,
     *     'name': string,
     *     'url': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
