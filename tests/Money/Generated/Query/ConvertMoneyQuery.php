<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Money\Generated\Query;

use Ruudk\GraphQLCodeGenerator\Money\Generated\Query\ConvertMoney\Data;
use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Currency;
use Ruudk\GraphQLCodeGenerator\Money\ValueObjects\Money;
use Ruudk\GraphQLCodeGenerator\TestClient;

// This file was automatically generated and should not be edited.
// Based on tests/Money/Test.graphql

final readonly class ConvertMoneyQuery {
    public const string OPERATION_NAME = 'ConvertMoney';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query ConvertMoney($amount: MoneyInput!, $targetCurrency: Currency!) {
          convertMoney(amount: $amount, targetCurrency: $targetCurrency) {
            amount
            currency
          }
          ...Converter
        }
        
        fragment Converter on Query {
          total {
            amount
            currency
          }
          subtotal: total {
            amount
            currency
          }
        }
        
        GRAPHQL;

    public function __construct(
        private TestClient $client,
    ) {}

    public function execute(
        Money $amount,
        Currency $targetCurrency,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'amount' => $amount,
                'targetCurrency' => $targetCurrency,
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [] // @phpstan-ignore argument.type
        );
    }
}
