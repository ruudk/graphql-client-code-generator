<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Twig\Generated\Query\Projectsd4cba6;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectList;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectList\Project;
use Ruudk\GraphQLCodeGenerator\Twig\Generated\Fragment\AdminProjectList\Viewer;
use Ruudk\GraphQLCodeGenerator\Twig\SomeController;

// This file was automatically generated and should not be edited.

#[Generated(
    source: SomeController::class,
    restricted: true,
    restrictInstantiation: true,
)]
final class Data
{
    public AdminProjectList $adminProjectList {
        get => $this->adminProjectList ??= new AdminProjectList($this->data);
    }

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
     * @var list<Error>
     */
    public readonly array $errors;

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
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
