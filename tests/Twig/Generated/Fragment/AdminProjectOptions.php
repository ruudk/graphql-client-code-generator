<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Enum\ProjectState;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

#[Generated(source: 'tests/Twig/templates/_project_options.html.twig')]
final class AdminProjectOptions
{
    public ?ProjectState $state {
        get => $this->state ??= $this->data['state'] !== null ? ProjectState::from($this->data['state']) : null;
    }

    /**
     * @throws NodeNotFoundException
     */
    public ProjectState $stateOrThrow {
        get => $this->state ?? throw NodeNotFoundException::create('Project', 'state');
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
