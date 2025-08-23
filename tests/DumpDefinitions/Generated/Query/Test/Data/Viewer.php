<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpDefinitions\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\DumpDefinitions\Generated\Query\Test\Data\Viewer\Project;

// This file was automatically generated and should not be edited.

/**
 * ... on Viewer {
 *   login
 *   projects {
 *     name
 *     description
 *   }
 * }
 */
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
