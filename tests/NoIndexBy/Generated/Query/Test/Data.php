<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\NoIndexBy\Generated\Query\Test\Data\Transactions;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

// This file was automatically generated and should not be edited.

#[Exclude]
final class Data
{
    public Transactions $transactions {
        get => $this->transactions ??= new Transactions($this->data['transactions']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'transactions': array{
     *         'edges': list<array{
     *             'node': array{
     *                 'id': string,
     *                 'workflow': null|array{
     *                     'request': null|array{
     *                         'id': string,
     *                         'items': list<array{
     *                             '__typename': string,
     *                             'id': string,
     *                         }>,
     *                     },
     *                 },
     *             },
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
