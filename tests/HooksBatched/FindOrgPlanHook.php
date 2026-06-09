<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Hook\OrganizationId;

/**
 * Batched org-level hook. Records every batch it is invoked with so the test
 * can measure its invocation count.
 */
#[Hook(name: 'findOrgPlan', requires: <<<'GRAPHQL'
    fragment OrganizationId on Organization {
      id
    }
    GRAPHQL, batched: true)]
final class FindOrgPlanHook
{
    /**
     * The `$inputs` array of every `__invoke` call, in order.
     *
     * @var list<array<int, OrganizationId>>
     */
    public array $batches = [];

    /**
     * @param array<string, OrgPlan> $plans
     */
    public function __construct(
        private readonly array $plans = [],
    ) {}

    /**
     * @param array<int, OrganizationId> $inputs
     * @return iterable<int, OrgPlan>
     */
    public function __invoke(array $inputs) : iterable
    {
        $this->batches[] = $inputs;

        foreach ($inputs as $key => $organization) {
            yield $key => $this->plans[$organization->id] ?? new OrgPlan($organization->id, 'free');
        }
    }
}
