<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectList\Project;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectList\Viewer;

// This file was automatically generated and should not be edited.

#[Generated(source: 'tests/Twig/templates/list.html.twig')]
final class AdminProjectList
{
    /**
     * @var list<Project>
     */
    public array $projects {
        get => $this->projects ??= array_map(fn($item) => new Project($item), $this->data['projects']);
    }

    public Viewer $viewer {
        get => $this->viewer ??= new Viewer($this->data['viewer']);
    }

    /**
     * @param array{
     *     'projects': list<array{
     *         'description': null|string,
     *         'id': string,
     *         'name': string,
     *         'state': null|string,
     *     }>,
     *     'viewer': array{
     *         'name': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
