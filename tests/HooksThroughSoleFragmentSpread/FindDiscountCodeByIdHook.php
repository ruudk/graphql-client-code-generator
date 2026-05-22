<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread\Generated\Hook\OrderDiscountId;

#[Hook(
    name: 'findDiscountCodeById',
    requires: <<<'GRAPHQL'
        fragment OrderDiscountId on Order {
          discountId
        }
        GRAPHQL
)]
final readonly class FindDiscountCodeByIdHook
{
    /**
     * @param array<string, DiscountCode> $discountCodes
     */
    public function __construct(
        private array $discountCodes = [],
    ) {}

    public function __invoke(OrderDiscountId $order) : ?DiscountCode
    {
        return $this->discountCodes[$order->discountId] ?? null;
    }
}
