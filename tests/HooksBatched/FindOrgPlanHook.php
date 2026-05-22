<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

use Ruudk\GraphQLCodeGenerator\Attribute\Hook;

/**
 * Batched org-level hook taking a single field. Records every batch it is
 * invoked with so the test can measure its invocation count.
 */
#[Hook(name: 'findOrgPlan', batched: true)]
final class FindOrgPlanHook
{
    /**
     * The `$inputs` array of every `__invoke` call, in order.
     *
     * @var list<array<int, array{string}>>
     */
    public array $batches = [];

    /**
     * @param array<string, OrgPlan> $plans
     */
    public function __construct(
        private readonly array $plans = [],
    ) {}

    /**
     * @param array<int, array{string}> $inputs
     * @return iterable<int, OrgPlan>
     */
    public function __invoke(array $inputs) : iterable
    {
        $this->batches[] = $inputs;

        foreach ($inputs as $key => [$id]) {
            yield $key => $this->plans[$id] ?? new OrgPlan($id, 'free');
        }
    }
}
