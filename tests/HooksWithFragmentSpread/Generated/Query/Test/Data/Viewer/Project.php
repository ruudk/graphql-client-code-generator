<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Query\Test\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Fragment\ProjectListing;

// This file was automatically generated and should not be edited.

final class Project
{
    public ProjectListing $projectListing {
        get => $this->projectListing ??= new ProjectListing($this->data, $this->hooks);
    }

    /**
     * @param array{
     *     'creator': array{
     *         'id': string,
     *         ...<int|string, mixed>,
     *     },
     *     'description': null|string,
     *     'name': string,
     *     ...<int|string, mixed>,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     *     ...<int|string, mixed>,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
