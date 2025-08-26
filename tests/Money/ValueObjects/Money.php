<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Money\ValueObjects;

use JsonSerializable;
use Override;

final readonly class Money implements JsonSerializable
{
    /**
     * @param numeric-string $amount
     */
    public function __construct(
        public string $amount,
        public Currency $currency,
    ) {}

    /**
     * @param numeric-string $amount
     */
    public static function EUR(string $amount) : self
    {
        return new self($amount, Currency::EUR());
    }

    /**
     * @param numeric-string $amount
     */
    public static function USD(string $amount) : self
    {
        return new self($amount, Currency::USD());
    }

    /**
     * @param numeric-string $amount
     */
    public static function GBP(string $amount) : self
    {
        return new self($amount, Currency::GBP());
    }

    public function equals(self $other) : bool
    {
        return $this->amount === $other->amount
            && $this->currency->equals($other->currency);
    }

    /**
     * @return array{amount: string, currency: string}
     */
    #[Override]
    public function jsonSerialize() : array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->code,
        ];
    }
}
