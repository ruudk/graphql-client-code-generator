<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\ApplicationDetails;
use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\UserDetails;
use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\ViewerName;

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

    public ?ApplicationDetails $applicationDetails {
        get {
            if (isset($this->applicationDetails)) {
                return $this->applicationDetails;
            }

            if ($this->data['__typename'] !== 'Application') {
                return $this->applicationDetails = null;
            }

            if (! array_key_exists('url', $this->data)) {
                return $this->applicationDetails = null;
            }

            return $this->applicationDetails = new ApplicationDetails($this->data);
        }
    }

    /**
     * @phpstan-assert-if-true !null $this->applicationDetails
     */
    public bool $isApplicationDetails {
        get => $this->isApplicationDetails ??= $this->data['__typename'] === 'Application';
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
     * @phpstan-assert-if-true !null $this->userDetails
     */
    public bool $isUserDetails {
        get => $this->isUserDetails ??= $this->data['__typename'] === 'User';
    }

    public ?ViewerName $viewerName {
        get => $this->viewerName ??= in_array($this->data['__typename'], ViewerName::POSSIBLE_TYPES, true) ? new ViewerName($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->viewerName
     */
    public bool $isViewerName {
        get => $this->isViewerName ??= in_array($this->data['__typename'], ViewerName::POSSIBLE_TYPES, true);
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
