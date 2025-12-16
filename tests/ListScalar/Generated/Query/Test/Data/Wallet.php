<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\ListScalar\Generated\Query\Test\Data;

use Ruudk\GraphQLCodeGenerator\ListScalar\ValueObjects\Currency;

// This file was automatically generated and should not be edited.

final class Wallet
{
    /**
     * @var list<Currency>
     */
    public array $currencies {
        get => $this->currencies ??= array_map(fn($item) => new Currency($item), $this->data['currencies']);
    }

    public string $name {
        get => $this->name ??= $this->data['name'];
    }

    /**
     * @param array{
     *     'currencies': list<string>,
     *     'name': string,
     * } $data
     */
    public function __construct(
        private readonly array $data,
    ) {}
}
