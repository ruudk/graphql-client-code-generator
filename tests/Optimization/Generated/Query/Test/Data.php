<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test\Data\Project;
use Ruudk\GraphQLCodeGenerator\Optimization\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
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
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'projects': list<array{
     *         'description': null|string,
     *         'name': string,
     *         'state': null|string,
     *     }>,
     *     'viewer': array{
     *         '__typename': string,
     *         'id': string,
     *         'idAlias': string,
     *         'login'?: string,
     *         'name': string,
     *         'url'?: string,
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
