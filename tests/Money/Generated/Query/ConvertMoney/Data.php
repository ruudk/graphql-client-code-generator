<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Money\Generated\Query\ConvertMoney;

use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Currency;
use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Money;

// This file was automatically generated and should not be edited.

final class Data
{
    public Money $convertMoney {
        get => $this->convertMoney ??= new Money(
            $this->data['convertMoney']['amount'],
            new Currency($this->data['convertMoney']['currency']),
        );
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'convertMoney': array{
     *         'amount': string,
     *         'currency': string,
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
