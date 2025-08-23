<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragments\Generated\Query\Test\Data;

// This file was automatically generated and should not be edited.

final class Project
{
    public ?string $description {
        get => $this->description ??= $this->data['description'] !== null ? $this->data['description'] : null;
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    public ?string $state {
        get => $this->state ??= $this->data['state'] !== null ? $this->data['state'] : null;
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
