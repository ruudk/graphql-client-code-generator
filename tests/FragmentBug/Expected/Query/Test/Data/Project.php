<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Expected\Fragment\ProjectView;

// This file was automatically generated and should not be edited.

/**
 * ... on Project {
 *   ...ProjectView
 * }
 */
final class Project
{
    public ProjectView $projectView {
        get => $this->projectView ??= new ProjectView($this->data);
    }

    /**
     * @param array{
     *     'creator': array{
     *         '__typename': string,
     *         'id': string,
     *         'name'?: string,
     *         'role'?: string,
     *     },
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
