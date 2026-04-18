<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\FindUsersByIdsHook;
use Ruudk\GraphQLCodeGenerator\HooksWithListReturn\Generated\Query\Test\Data\Viewer;

// This file was automatically generated and should not be edited.

final class Data
{
    public Viewer $viewer {
        get => $this->viewer ??= new Viewer($this->data['viewer'], $this->hooks);
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
     *             'contributorIds': list<string>,
     *             'name': string,
     *         }>,
     *     },
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     * }> $errors
     * @param array{
     *     'findUsersByIds': FindUsersByIdsHook,
     * } $hooks
     */
    public function __construct(
        private readonly array $data,
        array $errors,
        private readonly array $hooks,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
