<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Expected\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Fragment\ProjectView;

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
     *     'description': null|string,
     *     'name': string,
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
