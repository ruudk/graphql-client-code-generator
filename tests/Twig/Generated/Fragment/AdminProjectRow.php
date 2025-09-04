<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\NodeNotFoundException;

// This file was automatically generated and should not be edited.

#[Generated(source: 'tests/Twig/templates/_project_row.html.twig')]
final class AdminProjectRow
{
    public AdminProjectOptions $adminProjectOptions {
        get => $this->adminProjectOptions ??= new AdminProjectOptions($this->data);
    }

    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    /**
     * @throws NodeNotFoundException
     */
    public string $descriptionOrThrow {
        get => $this->description ?? throw NodeNotFoundException::create('Project', 'description');
    }

    public string $id {
        get => $this->id ??= $this->data['id'];
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'description': null|string,
     *     'id': string,
     *     'name': string,
     *     'state': null|string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
