<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\SpreadAddsFieldsToPolymorphicField\Generated\Query\Test\Data\Container;

// This file was automatically generated and should not be edited.

final class Data
{
    public Container $container {
        get => $this->container ??= new Container($this->data['container']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'container': array{
     *         'id': string,
     *         'item': array{
     *             '__typename': 'VariantA',
     *             'id': string,
     *             'valueA': string,
     *         }|array{
     *             '__typename': 'VariantB',
     *             'id': string,
     *             'valueB': string,
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
