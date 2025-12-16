<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListScalar\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\ListScalar\Generated\Query\Test\Data\Wallet;
use Ruudk\GraphQLCodeGenerator\ListScalar\ValueObjects\Currency;

// This file was automatically generated and should not be edited.

final class Data
{
    /**
     * @var list<Currency>
     */
    public array $supportedCurrencies {
        get => $this->supportedCurrencies ??= array_map(fn($item) => new Currency($item), $this->data['supportedCurrencies']);
    }

    public Wallet $wallet {
        get => $this->wallet ??= new Wallet($this->data['wallet']);
    }

    /**
     * @var list<Error>
     */
    public readonly array $errors;

    /**
     * @param array{
     *     'supportedCurrencies': list<string>,
     *     'wallet': array{
     *         'currencies': list<string>,
     *         'name': string,
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
