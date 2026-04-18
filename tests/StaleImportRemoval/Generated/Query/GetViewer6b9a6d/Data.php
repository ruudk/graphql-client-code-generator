<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewer6b9a6d;

use Ruudk\GraphQLCodeGenerator\StaleImportRemoval\Generated\Query\GetViewer6b9a6d\Data\Viewer;

// This file was automatically generated and should not be edited.

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
