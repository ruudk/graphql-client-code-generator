<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexBy\Expected\Query\Test\Data;

// This file was automatically generated and should not be edited.

/**
 * ... on Project {
 *   id
 *   name
 *   description
 * }
 */
final class Project
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
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
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
