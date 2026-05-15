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
        get {
            if (isset($this->userDetails)) {
                return $this->userDetails;
            }

            if ($this->data['__typename'] !== 'User') {
                return $this->userDetails = null;
            }

            if (! array_key_exists('login', $this->data)) {
                return $this->userDetails = null;
            }

            return $this->userDetails = new UserDetails($this->data);
        }
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
