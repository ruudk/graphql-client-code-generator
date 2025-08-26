<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Money\Generated\Fragment;

use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Currency;
use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Money;

// This file was automatically generated and should not be edited.

final class Converter
{
    public Money $subtotal {
        get => $this->subtotal ??= new Money(
            $this->data['subtotal']['amount'],
            new Currency($this->data['subtotal']['currency']),
        );
    }

    public Money $total {
        get => $this->total ??= new Money(
            $this->data['total']['amount'],
            new Currency($this->data['total']['currency']),
        );
    }

    /**
     * @param array{
     *     'subtotal': array{
     *         'amount': numeric-string,
     *         'currency': string,
     *     },
     *     'total': array{
     *         'amount': numeric-string,
     *         'currency': string,
     *     },
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
