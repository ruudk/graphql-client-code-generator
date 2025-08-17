<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Expected\Query\Test\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Fragment\ProjectView;

// This file was automatically generated and should not be edited.

/**
 * ... on Project {
 *   __typename
 *   ...ProjectView
 * }
 */
final class Project
{
    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?ProjectView $projectView {
        get => $this->projectView ??= in_array($this->data['__typename'], ProjectView::POSSIBLE_TYPES, true) ? new ProjectView($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->projectView
     */
    public bool $isProjectView {
        get => $this->isProjectView ??= in_array($this->data['__typename'], ProjectView::POSSIBLE_TYPES, true);
    }

    /**
     * @param array{
     *     '__typename': string,
     *     'description': null|string,
     *     'name': string,
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
