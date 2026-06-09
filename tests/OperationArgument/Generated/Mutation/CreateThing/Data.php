<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Mutation\CreateThing;

use Ruudk\GraphQLCodeGenerator\OperationArgument\Generated\Mutation\CreateThing\Data\CreateThing;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @api
     */
    public CreateThing $createThing {
        get => $this->createThing ??= new CreateThing($this->data['createThing']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'createThing': array{
     *         'id': string,
     *         'name': string,
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
