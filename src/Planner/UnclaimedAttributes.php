<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Planner;

/**
 * Finds the inline attributes that no config generates for: every config that read
 * the attribute left it to another scope, and no config has that scope.
 */
final readonly class UnclaimedAttributes
{
    /**
     * @return array<string, ?string>
     */
    public static function find(PlannerResult ...$results) : array
    {
        $unclaimed = [];
        foreach ($results as $result) {
            $unclaimed = [...$unclaimed, ...$result->skippedAttributes];
        }

        foreach ($results as $result) {
            foreach ($result->claimedAttributes as $location) {
                unset($unclaimed[$location]);
            }
        }

        ksort($unclaimed);

        return $unclaimed;
    }
}
