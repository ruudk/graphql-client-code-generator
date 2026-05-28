<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\NodeNotFoundException;
use Ruudk\GraphQLCodeGenerator\ThrowWhenNullDirective\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
{
    public Viewer $viewer {
        /**
         * @throws NodeNotFoundException
         */
        get => $this->viewer ??= $this->data['viewer'] !== null ? new Viewer($this->data['viewer']) : throw NodeNotFoundException::create('Query', 'viewer');
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'viewer': null|array{
     *         'description': null|string,
     *         'login': string,
     *         'project': null|array{
     *             'id': string,
     *             'name': string,
     *             ...,
     *         },
     *         ...,
     *     },
     *     ...,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
