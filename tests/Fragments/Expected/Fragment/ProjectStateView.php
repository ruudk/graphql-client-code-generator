<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Expected\Fragment;

use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Enum\ProjectState;

// This file was automatically generated and should not be edited.

/**
 * fragment ProjectStateView on Project {
 *   state
 * }
 */
final class ProjectStateView
{
    /**
     * @var list<string>
     */
    public const array POSSIBLE_TYPES = ['Project'];

    public ?ProjectState $state {
        get => $this->state ??= $this->data['state'] !== null ? ProjectState::from($this->data['state']) : null;
    }

    /**
     * @param array{
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
