<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\Inline1d8480\Data\Viewer;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedFrom;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\SomeController;

// This file was automatically generated and should not be edited.

#[GeneratedFrom(
    source: SomeController::class,
    restrict: true,
)]
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
