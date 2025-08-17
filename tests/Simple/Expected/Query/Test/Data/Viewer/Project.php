<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Simple\Expected\Query\Test\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\Simple\Expected\Enum\ProjectState;

// This file was automatically generated and should not be edited.

/**
 * ... on Project {
 *   name
 *   description
 *   state
 * }
 */
final class Project
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ?ProjectState $state {
        get => $this->state ??= $this->data['state'] !== null ? ProjectState::from($this->data['state']) : null;
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
