<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test;

use Ruudk\GraphQLCodeGenerator\InlineFragmentTypename\Generated\Mutation\Test\Data\AddCountry;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @api
     */
    public AddCountry $addCountry {
        get => $this->addCountry ??= new AddCountry($this->data['addCountry']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'addCountry': array{
     *         '__typename': string,
     *         'code'?: string,
     *         'id'?: string,
     *         'name'?: string,
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
