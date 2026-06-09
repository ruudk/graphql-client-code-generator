<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer\AsApplication;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer\AsUser;

// This file was automatically generated and should not be edited.

final class Viewer
{
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

    public string $name {
        get => $this->name ??= $this->data['name'];
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
