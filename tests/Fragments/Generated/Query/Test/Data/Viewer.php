<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\ApplicationDetails;
use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\UserDetails;
use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\ViewerName;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public ?ApplicationDetails $applicationDetails {
        get => $this->applicationDetails ??= $this->data['__typename'] === 'Application' ? new ApplicationDetails($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->applicationDetails
     */
    public bool $isApplicationDetails {
        get => $this->isApplicationDetails ??= $this->data['__typename'] === 'Application';
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

    public ?ViewerName $viewerName {
        get => $this->viewerName ??= in_array($this->data['__typename'], ['Application', 'User'], true) ? new ViewerName($this->data) : null;
    }

    /**
     * @api
     * @phpstan-assert-if-true !null $this->viewerName
     */
    public bool $isViewerName {
        get => $this->isViewerName ??= in_array($this->data['__typename'], ['Application', 'User'], true);
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
