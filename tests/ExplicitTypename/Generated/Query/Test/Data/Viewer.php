<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ExplicitTypename\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ExplicitTypename\Generated\Fragment\UserDetails;
use Ruudk\GraphQLCodeGenerator\ExplicitTypename\Generated\Query\Test\Data\Viewer\AsApplication;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsApplication $asApplication {
        get => $this->asApplication ??= $this->data['__typename'] === 'Application' ? new AsApplication($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->asApplication
     */
    public bool $isApplication {
        get => $this->isApplication ??= $this->data['__typename'] === 'Application';
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ?UserDetails $userDetails {
        get => $this->userDetails ??= $this->data['__typename'] === 'User' ? new UserDetails($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->userDetails
     */
    public bool $isUserDetails {
        get => $this->isUserDetails ??= $this->data['__typename'] === 'User';
    }

    /**
     * @param array{
     *     '__typename': 'Application',
     *     'name': string,
     *     'url': string,
     * }|array{
     *     '__typename': 'User',
     *     'login': string,
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
