<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpDefinitions\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

/**
 * ... on Project {
 *   name
 *   description
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
