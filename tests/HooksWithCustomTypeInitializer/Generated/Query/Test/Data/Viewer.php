<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Query\Test\Data\Viewer\Project;
use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Url;

// This file was automatically generated and should not be edited.

final class Viewer
{
    public Url $homepage {
        get => $this->homepage ??= new Url($this->data['homepage']['href']);
    }

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
     *     'homepage': array{
     *         'href': string,
     *     },
     *     'login': string,
     *     'projects': list<array{
     *         'creator': array{
     *             'id': string,
     *         },
     *         'name': string,
     *     }>,
     * } $data
     * @param array{
     *     'findUserById': FindUserByIdHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        private readonly array $hooks,
    ) {}
}
