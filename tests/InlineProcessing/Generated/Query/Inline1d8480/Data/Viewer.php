<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\Inline1d8480\Data;

use Ruudk\GraphQLCodeGenerator\Attribute\GeneratedFrom;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\Inline1d8480\Data\Viewer\Project;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\SomeController;

// This file was automatically generated and should not be edited.

#[GeneratedFrom(
    source: SomeController::class,
    restrict: true,
)]
final class Viewer
{
    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    /**
     * @var list<Project>
     */
    public array $projects {
        get => $this->projects ??= array_map(fn($item) => new Project($item), $this->data['projects']);
    }

    /**
     * @param array{
     *     'login': string,
     *     'projects': list<array{
     *         'description': null|string,
     *         'name': string,
     *     }>,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
