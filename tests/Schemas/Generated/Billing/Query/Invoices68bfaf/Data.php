<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\Invoices68bfaf;

use Ruudk\GraphQLCodeGenerator\Schemas\Generated\Billing\Query\Invoices68bfaf\Data\Bill;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Bill>
     */
    public array $bills {
        get => $this->bills ??= array_map(fn($item) => new Bill($item), $this->data['bills']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'bills': list<array{
     *         'id': string,
     *         'total': int,
     *         ...,
     *     }>,
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
