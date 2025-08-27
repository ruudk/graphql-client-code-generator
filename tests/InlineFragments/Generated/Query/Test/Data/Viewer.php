<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer\AsApplication;
use Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data\Viewer\AsUser;

// This file was automatically generated and should not be edited.

final class Viewer
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Application', 'User'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?AsApplication $asApplication {
        get {
            if (isset($this->asApplication)) {
                return $this->asApplication;
            }

            if ($this->data['__typename'] !== 'Application') {
                return $this->asApplication = null;
            }

            if (! array_key_exists('url', $this->data)) {
                return $this->asApplication = null;
            }

            return $this->asApplication = new AsApplication($this->data);
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->asApplication
     */
    public bool $isApplication {
        get => $this->isApplication ??= $this->data['__typename'] === 'Application';
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

            return $this->asUser = new AsUser($this->data);
        }
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

    /**
     * @param array{
     *     '__typename': string,
     *     'login'?: string,
     *     'name': string,
     *     'url'?: string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
