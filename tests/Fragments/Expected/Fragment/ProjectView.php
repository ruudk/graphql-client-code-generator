<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Expected\Fragment;

// This file was automatically generated and should not be edited.

/**
 * fragment ProjectView on Project {
 *   __typename
 *   name
 *   description
 *   ...ProjectStateView
 * }
 */
final class ProjectView
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Project'];

    public string $__typename {
        get => $this->__typename ??= $this->data['__typename'];
    }

    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ?ProjectStateView $projectStateView {
        get => $this->projectStateView ??= in_array($this->data['__typename'], ProjectStateView::POSSIBLE_TYPES, true) ? new ProjectStateView($this->data) : null;
    }

    /**
     * @phpstan-assert-if-true !null $this->projectStateView
     */
    public bool $isProjectStateView {
        get => $this->isProjectStateView ??= in_array($this->data['__typename'], ProjectStateView::POSSIBLE_TYPES, true);
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
