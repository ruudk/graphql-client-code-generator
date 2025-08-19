<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\IndexBy\Expected\Query\Test;

use Ruudk\GraphQLCodeGenerator\IndexBy\Expected\Query\Test\Data\Project;

// This file was automatically generated and should not be edited.

/**
 * query Test {
 *   projects @indexBy(field: "id") {
 *     id
 *     name
 *     description
 *   }
 * }
 */
final class Data
{
    /**
     * @var array<string,Project>
     */
    public array $projects {
        get => $this->projects ??= array_combine(
            array_column($this->data['projects'], 'id'),
            array_map(fn($item) => new Project($item), $this->data['projects']),
        );
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
