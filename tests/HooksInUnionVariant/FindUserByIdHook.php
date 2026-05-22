<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksInUnionVariant;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksInUnionVariant\Generated\Hook\VariantAId;

#[Hook(
    name: 'findUserById',
    requires: <<<'GRAPHQL'
        fragment VariantAId on VariantA {
          id
        }
        GRAPHQL
)]
final readonly class FindUserByIdHook
{
    /**
     * @param array<string, User> $users
     */
    public function __construct(
        private array $users = [],
    ) {}

    public function __invoke(VariantAId $variant) : ?User
    {
        return $this->users[$variant->id] ?? null;
    }
}
