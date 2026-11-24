<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

use PHPUnit\Framework\TestCase;

final class UnclaimedAttributesTest extends TestCase
{
    public function testFindsNothingWithoutSkippedAttributes() : void
    {
        self::assertSame([], UnclaimedAttributes::find(
            $this->plan(['App\A::__construct::$query'], []),
        ));
    }

    public function testFindsNothingWhenAnotherConfigClaimsWhatOneSkipped() : void
    {
        self::assertSame([], UnclaimedAttributes::find(
            $this->plan(['App\A::__construct::$query'], [
                'App\B::__construct::$query' => 'shipping',
            ]),
            $this->plan(['App\B::__construct::$query'], [
                'App\A::__construct::$query' => 'billing',
            ]),
        ));
    }

    public function testFindsTheAttributesNoConfigClaims() : void
    {
        self::assertSame(
            [
                'App\B::__construct::$query' => 'Shipping',
                'App\C::__construct::$query' => null,
            ],
            UnclaimedAttributes::find(
                $this->plan(['App\A::__construct::$query'], [
                    'App\C::__construct::$query' => null,
                    'App\B::__construct::$query' => 'Shipping',
                ]),
                $this->plan([], [
                    'App\A::__construct::$query' => 'billing',
                    'App\B::__construct::$query' => 'Shipping',
                    'App\C::__construct::$query' => null,
                ]),
            ),
        );
    }

    public function testFindsNothingWithoutResults() : void
    {
        self::assertSame([], UnclaimedAttributes::find());
    }

    /**
     * @param list<string> $claimed
     * @param array<string, ?string> $skipped
     */
    private function plan(array $claimed, array $skipped) : PlannerResult
    {
        $result = new PlannerResult();
        $result->setAttributes($claimed, $skipped);

        return $result;
    }
}
