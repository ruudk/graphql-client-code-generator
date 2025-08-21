<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment\ProjectView\Creator;

// This file was automatically generated and should not be edited.

/**
 * fragment ProjectView on Project {
 *   name
 *   creator {
 *     __typename
 *     ... on User {
 *       name
 *     }
 *     ... on Admin {
 *       name
 *       role
 *     }
 *   }
 *   ...ProjectStateView
 * }
 */
final class ProjectView
{
    public Creator $creator {
        get => $this->creator ??= new Creator($this->data['creator']);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ProjectStateView $projectStateView {
        get => $this->projectStateView ??= new ProjectStateView($this->data);
    }

    /**
     * @param array{
     *     'creator': array{
     *         '__typename': string,
     *         'id': string,
     *         'name': string,
     *         'role': string,
     *     },
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
