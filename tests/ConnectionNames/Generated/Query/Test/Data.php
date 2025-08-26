<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test\Data\ProjectConnection;
use Ruudk\GraphQLCodeGenerator\ConnectionNames\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?ProjectConnection $projects {
        get => $this->projects ??= $this->data['projects'] !== null ? new ProjectConnection($this->data['projects']) : null;
    }

    public ?Viewer $viewer {
        get => $this->viewer ??= $this->data['viewer'] !== null ? new Viewer($this->data['viewer']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'projects': null|array{
     *         'edges': list<array{
     *             'cursor': string,
     *             'node': array{
     *                 'id': string,
     *                 'name': string,
     *                 'status': string,
     *             },
     *         }>,
     *         'pageInfo': array{
     *             'endCursor': null|string,
     *             'hasNextPage': bool,
     *             'hasPreviousPage': bool,
     *             'startCursor': null|string,
     *         },
     *     },
     *     'viewer': null|array{
     *         'createdAt': scalar,
     *         'id': string,
     *         'name': string,
     *         'role': string,
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
