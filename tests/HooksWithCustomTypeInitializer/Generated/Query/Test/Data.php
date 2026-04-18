<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\FindUserByIdHook;
use Ruudk\GraphQLCodeGenerator\HooksWithCustomTypeInitializer\Generated\Query\Test\Data\Viewer;

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
     *         'homepage': array{
     *             'href': string,
     *         },
     *         'login': string,
     *         'projects': list<array{
     *             'creator': array{
     *                 'id': string,
     *             },
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
     *     'findUserById': FindUserByIdHook,
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
