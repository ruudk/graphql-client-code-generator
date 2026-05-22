<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\HooksBatched;

use Override;
use Ruudk\GraphQLCodeGenerator\Config\Config;
use Ruudk\GraphQLCodeGenerator\GraphQLTestCase;
use Ruudk\GraphQLCodeGenerator\HooksBatched\Generated\Query\Test\TestQuery;

/**
 * Verifies batched hook resolution ("hook loaders").
 *
 * Each hook here opts in with `#[Hook(name: '...', batched: true)]`. Instead of
 * invoking the hook once per object instance (the legacy N+1), the generator
 * emits one `HookLoader` per hook. On first access of any hooked property the
 * loader walks the typed object graph once, collects every occurrence's input,
 * and invokes the hook a single time with the whole batch.
 *
 * The query in Test.graphql has:
 *   - `findOrgPlan` on each Organization        (one list)
 *   - `findUserById` on each Repository         (list nested in a list)
 *   - `computeAccess` on each Repository         (list nested in a list)
 *
 * With 3 organizations x 2 repositories, every hook is invoked exactly once —
 * `findOrgPlan` with a batch of 3, `findUserById` and `computeAccess` with a
 * batch of 6 — no matter how many instances carry the property.
 */
final class HooksBatchedTest extends GraphQLTestCase
{
    #[Override]
    public function getConfig() : Config
    {
        return parent::getConfig()
            ->withHook(FindUserByIdHook::class)
            ->withHook(ComputeAccessHook::class)
            ->withHook(FindOrgPlanHook::class);
    }

    public function testGenerate() : void
    {
        $this->assertActualMatchesExpected();
    }

    public function testHooksAreBatchedAndInvokedOncePerOperation() : void
    {
        $findUserById = new FindUserByIdHook([
            'user-1' => new User('user-1', 'Alice'),
            'user-2' => new User('user-2', 'Bob'),
        ]);
        $computeAccess = new ComputeAccessHook();
        $findOrgPlan = new FindOrgPlanHook([
            'org-1' => new OrgPlan('org-1', 'enterprise'),
            'org-2' => new OrgPlan('org-2', 'team'),
            'org-3' => new OrgPlan('org-3', 'free'),
        ]);

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'organizations' => [
                        $this->organization('org-1', 'Acme', [
                            $this->repository('repo-1', 'web', 'user-1', 'user-2'),
                            $this->repository('repo-2', 'api', 'user-1', 'user-1'),
                        ]),
                        $this->organization('org-2', 'Globex', [
                            $this->repository('repo-3', 'infra', 'user-2', 'user-1'),
                            $this->repository('repo-4', 'docs', 'user-1', 'user-2'),
                        ]),
                        $this->organization('org-3', 'Initech', [
                            $this->repository('repo-5', 'mobile', 'user-2', 'user-2'),
                            $this->repository('repo-6', 'cli', 'user-1', 'user-1'),
                        ]),
                    ],
                ],
            ]),
            [
                'findUserById' => $findUserById,
                'computeAccess' => $computeAccess,
                'findOrgPlan' => $findOrgPlan,
            ],
        )->execute();

        // Walk the whole result, touching every hooked property.
        $ownerNames = [];
        $accessFlags = [];
        $planTiers = [];

        foreach ($result->organizations as $organization) {
            $planTiers[] = $organization->plan->tier;

            foreach ($organization->repositories as $repository) {
                $ownerNames[] = $repository->owner?->name;
                $accessFlags[] = $repository->access->ownerIsReviewer;
            }
        }

        // The resolved values are correct.
        self::assertSame(['enterprise', 'team', 'free'], $planTiers);
        self::assertSame(['Alice', 'Alice', 'Bob', 'Alice', 'Bob', 'Alice'], $ownerNames);
        self::assertSame([false, true, false, false, true, true], $accessFlags);

        // --- The batching evidence -------------------------------------------

        // Each hook ran exactly once (the outer array has a single entry) and
        // that single batch carried the distinct inputs, de-duplicated by
        // value and in first-seen graph order.

        // Org-level hook: one batch, all 3 organizations distinct.
        self::assertSame(
            [[['org-1'], ['org-2'], ['org-3']]],
            $findOrgPlan->batches,
        );

        // Repository-level single-field hook: 6 repositories, but only 2
        // distinct owner ids — what used to be 6 separate invocations is now
        // one call with 2 inputs.
        self::assertSame(
            [[['user-1'], ['user-2']]],
            $findUserById->batches,
        );

        // Repository-level two-field hook: 6 repositories collapse to 4
        // distinct (ownerId, reviewerId) tuples.
        self::assertSame(
            [[
                ['user-1', 'user-2'],
                ['user-1', 'user-1'],
                ['user-2', 'user-1'],
                ['user-2', 'user-2'],
            ]],
            $computeAccess->batches,
        );
    }

    public function testHooksResolveLazilyAndDoNotRunBeforeAccess() : void
    {
        $findUserById = new FindUserByIdHook([
            'user-1' => new User('user-1', 'Alice'),
        ]);
        $computeAccess = new ComputeAccessHook();
        $findOrgPlan = new FindOrgPlanHook();

        new TestQuery(
            $this->getClient([
                'data' => [
                    'organizations' => [
                        $this->organization('org-1', 'Acme', [
                            $this->repository('repo-1', 'web', 'user-1', 'user-1'),
                        ]),
                    ],
                ],
            ]),
            [
                'findUserById' => $findUserById,
                'computeAccess' => $computeAccess,
                'findOrgPlan' => $findOrgPlan,
            ],
        )->execute();

        // execute() builds the Data graph and the loaders, but no hooked
        // property has been read yet, so no hook has run.
        self::assertSame([], $findUserById->batches);
        self::assertSame([], $computeAccess->batches);
        self::assertSame([], $findOrgPlan->batches);
    }

    public function testRepeatedAccessTriggersTheBatchOnlyOnce() : void
    {
        $findUserById = new FindUserByIdHook([
            'user-1' => new User('user-1', 'Alice'),
        ]);
        $computeAccess = new ComputeAccessHook();
        $findOrgPlan = new FindOrgPlanHook();

        $result = new TestQuery(
            $this->getClient([
                'data' => [
                    'organizations' => [
                        $this->organization('org-1', 'Acme', [
                            $this->repository('repo-1', 'web', 'user-1', 'user-1'),
                            $this->repository('repo-2', 'api', 'user-1', 'user-1'),
                        ]),
                    ],
                ],
            ]),
            [
                'findUserById' => $findUserById,
                'computeAccess' => $computeAccess,
                'findOrgPlan' => $findOrgPlan,
            ],
        )->execute();

        [$first, $second] = $result->organizations[0]->repositories;

        // The first access of any hooked property triggers the one batch; every
        // later access — same instance or a sibling — is served from the cache.
        $owner = $first->owner;
        self::assertNotNull($owner);
        self::assertSame('Alice', $owner->name);
        self::assertSame($owner, $first->owner);
        self::assertSame('Alice', $second->owner?->name);

        // Both repositories share owner "user-1", so the de-duplicated batch
        // holds a single input and both resolve to the same User instance.
        self::assertCount(1, $findUserById->batches);
        self::assertCount(1, $findUserById->batches[0]);
        self::assertSame($owner, $second->owner);
    }

    /**
     * @param list<array<string, mixed>> $repositories
     * @return array<string, mixed>
     */
    private function organization(string $id, string $name, array $repositories) : array
    {
        return [
            'id' => $id,
            'name' => $name,
            'repositories' => $repositories,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function repository(string $id, string $name, string $ownerId, string $reviewerId) : array
    {
        return [
            'id' => $id,
            'name' => $name,
            'ownerId' => $ownerId,
            'reviewerId' => $reviewerId,
        ];
    }
}
