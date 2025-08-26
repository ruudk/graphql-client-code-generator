<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails;

use Ruudk\GraphQLCodeGenerator\FragmentSpreadBug\Generated\Query\GetTransactionDetails\Data\Transaction;

// This file was automatically generated and should not be edited.

final class Data
{
    public ?Transaction $transaction {
        get => $this->transaction ??= $this->data['transaction'] !== null ? new Transaction($this->data['transaction']) : null;
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'transaction': null|array{
     *         'id': string,
     *         'state': string,
     *         'transfers': list<array{
     *             'createdAt': string,
     *             'id': string,
     *             'state': string,
     *             'total': array{
     *                 'amount': string,
     *                 'currency': string,
     *             },
     *             'transferReversals': list<array{
     *                 'createdAt': string,
     *                 'id': string,
     *                 'resolutions': null|list<array{
     *                     'createdAt': string,
     *                     'id': string,
     *                     'total': array{
     *                         'amount': string,
     *                         'currency': string,
     *                     },
     *                 }>,
     *                 'returnMethod': null|string,
     *                 'returnedAt': null|string,
     *                 'state': string,
     *                 'total': array{
     *                     'amount': string,
     *                     'currency': string,
     *                 },
     *             }>,
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
