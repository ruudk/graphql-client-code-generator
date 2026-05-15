<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Query\Test;

use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\FindDiscountCodeByIdHook;
use Ruudk\GraphQLCodeGenerator\TestClient;
use Stringable;

// This file was automatically generated and should not be edited.

final readonly class TestQuery {
    public const string OPERATION_NAME = 'Test';
    public const string OPERATION_DEFINITION = <<<'GRAPHQL'
        query Test($paymentFlowId: ID!) {
          paymentFlow(id: $paymentFlowId) {
            ...ShowPaymentFlow
          }
        }
        
        fragment ShowPaymentFlow on PaymentFlow {
          id
          order {
            id
            discountId
          }
        }
        
        GRAPHQL;

    /**
     * @param array{
     *     'findDiscountCodeById': FindDiscountCodeByIdHook,
     * } $hooks
     */
    public function __construct(
        private TestClient $client,
        private array $hooks,
    ) {}

    /**
     * @api
     */
    public function execute(
        Stringable|string $paymentFlowId,
    ) : Data {
        $data = $this->client->graphql(
            self::OPERATION_DEFINITION,
            [
                'paymentFlowId' => (string) $paymentFlowId,
            ],
            self::OPERATION_NAME,
        );

        return new Data(
            $data['data'] ?? [], // @phpstan-ignore argument.type
            $data['errors'] ?? [], // @phpstan-ignore argument.type
            $this->hooks,
        );
    }
}
