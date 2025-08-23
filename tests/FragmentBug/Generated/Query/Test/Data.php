<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\FragmentBug\Generated\Query\Test\Data\Project;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Project>
     */
    public array $projects {
        get => $this->projects ??= array_map(fn($item) => new Project($item), $this->data['projects']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'projects': list<array{
     *         'creator': array{
     *             '__typename': string,
     *             'id': string,
     *             'name'?: string,
     *             'role'?: string,
     *         },
     *         'name': string,
     *     }>,
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
