<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\DumpDefinitions\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\DumpDefinitions\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

/**
 * query Test {
 *   viewer {
 *     login
 *     projects {
 *       name
 *       description
 *     }
 *   }
 * }
 */
final class Data
{
    public Viewer $viewer {
        get => $this->viewer ??= new Viewer($this->data['viewer']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'viewer': array{
     *         'login': string,
     *         'projects': list<array{
     *             'description': null|string,
     *             'name': string,
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
