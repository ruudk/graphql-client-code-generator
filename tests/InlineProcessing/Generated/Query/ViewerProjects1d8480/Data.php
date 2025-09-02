<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\ViewerProjects1d8480;

use Ruudk\GraphQLCodeGenerator\Attribute\Generated;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\Generated\Query\ViewerProjects1d8480\Data\Viewer;
use Ruudk\GraphQLCodeGenerator\InlineProcessing\SomeController;

// This file was automatically generated and should not be edited.

#[Generated(
    source: SomeController::class,
    restricted: true,
    restrictInstantiation: true,
)]
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
