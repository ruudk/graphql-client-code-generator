<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ListTypename\Generated\Query\Test\Data\Field;

// This file was automatically generated and should not be edited.

final class Data
{
    public Field $field {
        get => $this->field ??= new Field($this->data['field']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'field': array{
     *         'multiList': list<array{
     *             '__typename': string,
     *             'id': string,
     *             ...<int|string, mixed>,
     *         }>,
     *         'single': array{
     *             '__typename': string,
     *             ...<int|string, mixed>,
     *         },
     *         'soleList': list<array{
     *             '__typename': string,
     *             ...<int|string, mixed>,
     *         }>,
     *         ...<int|string, mixed>,
     *     },
     *     ...<int|string, mixed>,
     * } $data
     * @param list<array{
     *     'code': string,
     *     'debugMessage'?: string,
     *     'message': string,
     *     ...<int|string, mixed>,
     * }> $errors
     */
    public function __construct(
        private readonly array $data,
        array $errors,
    ) {
        $this->errors = array_map(fn(array $error) => new Error($error), $errors);
    }
}
