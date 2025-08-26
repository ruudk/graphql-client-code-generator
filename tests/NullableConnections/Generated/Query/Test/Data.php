<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\NullableConnections\Generated\Query\Test\Data\ProjectConnection;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?ProjectConnection $projects {
        get => $this->projects ??= $this->data['projects'] !== null ? new ProjectConnection($this->data['projects']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'projects': null|array{
     *         'edges': null|list<null|array{
     *             'cursor': string,
     *             'node': array{
     *                 'id': string,
     *                 'name': string,
     *             },
     *         }>,
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
