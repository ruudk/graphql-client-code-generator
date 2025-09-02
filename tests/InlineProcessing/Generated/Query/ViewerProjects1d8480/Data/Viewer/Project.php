<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\ViewerProjects1d8480\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\SomeController;

// This file was automatically generated and should not be edited.

#[Generated(
    source: SomeController::class,
    restricted: true,
    restrictInstantiation: true,
)]
final class Project
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    /**
     * @throws NodeNotFoundException
     */
    public string $descriptionOrThrow {
        get => $this->description ?? throw NodeNotFoundException::create('Project', 'description');
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
