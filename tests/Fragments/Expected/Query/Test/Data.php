<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Fragments\Expected\Query\Test;

use Ruudk\GraphQLCodeGenerator\Fragments\Expected\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

/**
 * query Test {
 *   viewer {
 *     __typename
 *     ...ViewerDetails
 *     projects {
 *       __typename
 *       ...ProjectView
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
     *         '__typename': string,
     *         'login': string,
     *         'projects': list<array{
     *             '__typename': string,
     *             'description': null|string,
     *             'name': string,
     *             'state': null|string,
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
