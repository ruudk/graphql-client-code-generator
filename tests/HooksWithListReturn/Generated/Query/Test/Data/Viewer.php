<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\FindUsersByIdsHook;
use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Query\Test\Data\Viewer\Project;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public string $login {
        get => $this->login ??= $this->data['login'];
    }

    /**
     * @var list<Project>
     */
    public array $projects {
        get => $this->projects ??= array_map(fn($item) => new Project($item, $this->hooks), $this->data['projects']);
    }

    /**
     * @param array{
     *     'login': string,
     *     'projects': list<array{
     *         'contributorIds': list<string>,
     *         'name': string,
     *     }>,
     * } $data
     * @param array{
     *     'findUsersByIds': FindUsersByIdsHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
