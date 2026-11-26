<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\CheckoutViewer0d4a41;

use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\CheckoutViewer0d4a41\Data\Viewer;

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
