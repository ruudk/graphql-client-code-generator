<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\HooksWithFragmentSpread\FindUserByIdHook;

// This file was automatically generated and should not be edited.

final class ProjectListing
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public ProjectSummary $projectSummary {
        get => $this->projectSummary ??= new ProjectSummary($this->data, $this->hooks);
    }

    /**
     * @param array{
     *     'creator': array{
     *         'id': string,
     *     },
     *     'description': null|string,
     *     'name': string,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
