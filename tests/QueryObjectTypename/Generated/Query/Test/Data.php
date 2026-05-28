<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\Order;
use Ruudk\GraphQLCodeGenerator\QueryObjectTypename\Generated\Query\Test\Data\SupportedCountry;

// This file was automatically generated and should not be edited.

final class Data
{
    public Order $order {
        get => $this->order ??= new Order($this->data['order']);
    }

    public SupportedCountry $supportedCountry {
        get => $this->supportedCountry ??= new SupportedCountry($this->data['supportedCountry']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'order': array{
     *         '__typename': string,
     *         'fxFee'?: null|array{
     *             '__typename': string,
     *             ...<int|string, mixed>,
     *         },
     *         'id'?: string,
     *         ...<int|string, mixed>,
     *     },
     *     'supportedCountry': array{
     *         'error': null|array{
     *             '__typename': string,
     *             ...<int|string, mixed>,
     *         },
     *         'info': null|array{
     *             'name': string,
     *             ...<int|string, mixed>,
     *         },
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
