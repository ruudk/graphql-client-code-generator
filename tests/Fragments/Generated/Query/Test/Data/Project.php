<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\Fragments\Generated\Fragment\ProjectView;

// This file was automatically generated and should not be edited.

final class Project
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

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
