<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksThroughSoleFragmentSpread;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

#[Hook(name: 'findDiscountCodeById')]
final readonly class FindDiscountCodeByIdHook
{
    /**
     * @param array<string, DiscountCode> $discountCodes
     */
    public function __construct(
        private array $discountCodes = [],
    ) {}

    public function __invoke(string $id) : ?DiscountCode
    {
        return $this->discountCodes[$id] ?? null;
    }
}
